<?php

namespace EasyPack\Services;

use Illuminate\Support\Facades\Route;

class Navigator
{
    /**
     * Navigation items grouped by section.
     *
     * @var array
     */
    protected static array $items = [];

    /**
     * Add a navigation item to a specific section.
     *
     * @param array $item
     * @param string $section
     * @return void
     */
    public static function addItem(array $item, string $section = 'default'): void
    {
        if (!isset(self::$items[$section])) {
            self::$items[$section] = [];
        }

        // Set defaults
        $item = array_merge([
            'text' => '',
            'icon_class' => 'fas fa-circle',
            'resource' => null,
            'url' => null,
            'permission' => null,
            'order' => 100,
            'badge' => null,
            'badge_class' => 'bg-primary',
            'children' => [],
        ], $item);

        self::$items[$section][] = $item;
    }

    /**
     * Get navigation items for a section.
     *
     * @param string $section
     * @return array
     */
    public static function getItems(string $section = 'default'): array
    {
        $items = self::$items[$section] ?? [];

        // Filter items based on permissions and route existence
        $items = array_filter($items, function ($item) {
            // Check if route exists
            if (!empty($item['resource']) && !Route::has($item['resource'])) {
                return false;
            }

            // Check permission if specified
            if (!empty($item['permission'])) {
                // If user is not authenticated, hide items with permission requirements
                if (!auth()->check()) {
                    return false;
                }
                // Check if user has the required permission
                if (!auth()->user()->can($item['permission'])) {
                    return false;
                }
            }

            return true;
        });

        // Sort by order
        usort($items, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));

        return $items;
    }

    /**
     * Get all sections with their items.
     *
     * @return array
     */
    public static function getAllSections(): array
    {
        return self::$items;
    }

    /**
     * Clear all navigation items.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$items = [];
    }

    /**
     * Check if a section has any items.
     *
     * @param string $section
     * @return bool
     */
    public static function hasItems(string $section): bool
    {
        return count(self::getItems($section)) > 0;
    }

    /**
     * Get the URL for a navigation item.
     *
     * @param array $item
     * @return string|null
     */
    public static function getUrl(array $item): ?string
    {
        if (!empty($item['url'])) {
            return $item['url'];
        }

        if (!empty($item['resource']) && Route::has($item['resource'])) {
            return route($item['resource']);
        }

        return null;
    }

    /**
     * Check if a navigation item is active.
     *
     * @param array $item
     * @return bool
     */
    public static function isActive(array $item): bool
    {
        if (!empty($item['resource'])) {
            $routeName = $item['resource'];
            // Check if current route matches or starts with the item's route
            $currentRoute = request()->route()?->getName();
            if ($currentRoute === $routeName) {
                return true;
            }
            // Check prefix match (e.g., manage.users.* matches manage.users.index)
            $prefix = explode('.', $routeName);
            array_pop($prefix);
            $prefix = implode('.', $prefix);
            if ($prefix && str_starts_with($currentRoute ?? '', $prefix)) {
                return true;
            }
        }

        return false;
    }
}
