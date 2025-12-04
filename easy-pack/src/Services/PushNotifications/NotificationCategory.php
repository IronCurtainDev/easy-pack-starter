<?php

namespace EasyPack\Services\PushNotifications;

/**
 * Notification category constants and helpers.
 */
class NotificationCategory
{
    /**
     * General notifications - default category.
     */
    public const GENERAL = 'general';

    /**
     * Promotional/marketing notifications.
     */
    public const PROMOTIONAL = 'promotional';

    /**
     * Transactional notifications (orders, payments, etc.).
     */
    public const TRANSACTIONAL = 'transactional';

    /**
     * System notifications (updates, security, etc.).
     */
    public const SYSTEM = 'system';

    /**
     * Social notifications (messages, follows, likes, etc.).
     */
    public const SOCIAL = 'social';

    /**
     * Reminder notifications.
     */
    public const REMINDER = 'reminder';

    /**
     * Get all available categories with descriptions.
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        return [
            self::GENERAL => [
                'name' => 'General',
                'description' => 'General notifications and updates',
                'default_enabled' => true,
                'can_disable' => true,
            ],
            self::PROMOTIONAL => [
                'name' => 'Promotional',
                'description' => 'Offers, discounts, and marketing updates',
                'default_enabled' => true,
                'can_disable' => true,
            ],
            self::TRANSACTIONAL => [
                'name' => 'Transactional',
                'description' => 'Orders, payments, and account activity',
                'default_enabled' => true,
                'can_disable' => false, // Critical - cannot disable
            ],
            self::SYSTEM => [
                'name' => 'System',
                'description' => 'App updates, security alerts, and system messages',
                'default_enabled' => true,
                'can_disable' => false, // Critical - cannot disable
            ],
            self::SOCIAL => [
                'name' => 'Social',
                'description' => 'Messages, follows, likes, and social activity',
                'default_enabled' => true,
                'can_disable' => true,
            ],
            self::REMINDER => [
                'name' => 'Reminders',
                'description' => 'Scheduled reminders and alerts',
                'default_enabled' => true,
                'can_disable' => true,
            ],
        ];
    }

    /**
     * Get category names only.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * Check if a category is valid.
     *
     * @param string $category
     * @return bool
     */
    public static function isValid(string $category): bool
    {
        return in_array($category, self::names());
    }

    /**
     * Get categories that can be disabled by users.
     *
     * @return array<string>
     */
    public static function disableable(): array
    {
        return array_keys(array_filter(self::all(), fn($cat) => $cat['can_disable']));
    }

    /**
     * Get categories that cannot be disabled (critical).
     *
     * @return array<string>
     */
    public static function critical(): array
    {
        return array_keys(array_filter(self::all(), fn($cat) => !$cat['can_disable']));
    }

    /**
     * Get default preferences for a new user.
     *
     * @return array<string, bool>
     */
    public static function defaultPreferences(): array
    {
        $preferences = [];
        foreach (self::all() as $key => $category) {
            $preferences[$key] = $category['default_enabled'];
        }
        return $preferences;
    }

    /**
     * Get Android notification channel ID for a category.
     *
     * @param string $category
     * @return string
     */
    public static function androidChannelId(string $category): string
    {
        return config("push-notifications.categories.{$category}.android_channel", "channel_{$category}");
    }

    /**
     * Get default sound for a category.
     *
     * @param string $category
     * @return string|null
     */
    public static function defaultSound(string $category): ?string
    {
        return config("push-notifications.categories.{$category}.sound");
    }
}
