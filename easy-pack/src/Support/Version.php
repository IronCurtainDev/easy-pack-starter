<?php

namespace EasyPack\Support;

use Illuminate\Support\Facades\App;

/**
 * Version Utility Class
 * 
 * Provides version detection and compatibility checking for Easy Pack.
 * This helps with conditional logic when APIs differ between Laravel/package versions.
 */
class Version
{
    /**
     * Easy Pack version.
     */
    public const EASYPACK_VERSION = '1.0.0';

    /**
     * Minimum supported Laravel version.
     */
    public const MIN_LARAVEL_VERSION = '11.0';

    /**
     * Maximum tested Laravel version.
     */
    public const MAX_LARAVEL_VERSION = '12.99';

    /**
     * Supported package versions.
     */
    public const SUPPORTED_PACKAGES = [
        'laravel/sanctum' => ['4.0'],
        'spatie/laravel-permission' => ['5.0', '6.0'],
        'spatie/laravel-medialibrary' => ['10.0', '11.0'],
    ];

    /**
     * Get the Easy Pack version.
     */
    public static function oxygen(): string
    {
        return static::EASYPACK_VERSION;
    }

    /**
     * Get the Laravel framework version.
     */
    public static function laravel(): string
    {
        return App::version();
    }

    /**
     * Get the major Laravel version number.
     */
    public static function laravelMajor(): int
    {
        return (int) explode('.', static::laravel())[0];
    }

    /**
     * Get the minor Laravel version number.
     */
    public static function laravelMinor(): int
    {
        $parts = explode('.', static::laravel());
        return (int) ($parts[1] ?? 0);
    }

    /**
     * Check if current Laravel version is at least the specified version.
     */
    public static function laravelAtLeast(string $version): bool
    {
        return version_compare(static::laravel(), $version, '>=');
    }

    /**
     * Check if current Laravel version is below the specified version.
     */
    public static function laravelBelow(string $version): bool
    {
        return version_compare(static::laravel(), $version, '<');
    }

    /**
     * Check if Laravel is a specific major version.
     */
    public static function isLaravel(int $majorVersion): bool
    {
        return static::laravelMajor() === $majorVersion;
    }

    /**
     * Check if a package is installed.
     */
    public static function hasPackage(string $package): bool
    {
        return class_exists(static::getPackageClass($package));
    }

    /**
     * Get a package's version if installed.
     */
    public static function packageVersion(string $package): ?string
    {
        $installedPath = base_path('vendor/composer/installed.json');
        
        if (!file_exists($installedPath)) {
            return null;
        }

        $installed = json_decode(file_get_contents($installedPath), true);
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $pkg) {
            if (($pkg['name'] ?? '') === $package) {
                return $pkg['version'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check if a package version is at least the specified version.
     */
    public static function packageAtLeast(string $package, string $version): bool
    {
        $current = static::packageVersion($package);
        
        if (!$current) {
            return false;
        }

        // Remove 'v' prefix if present
        $current = ltrim($current, 'v');
        $version = ltrim($version, 'v');

        return version_compare($current, $version, '>=');
    }

    /**
     * Get PHP version.
     */
    public static function php(): string
    {
        return PHP_VERSION;
    }

    /**
     * Check if PHP version is at least the specified version.
     */
    public static function phpAtLeast(string $version): bool
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }

    /**
     * Check if a feature is supported in the current environment.
     */
    public static function supports(string $feature): bool
    {
        return match ($feature) {
            // Laravel 11+ features
            'typed-casts' => static::laravelAtLeast('11.0'),
            'casts-method' => static::laravelAtLeast('11.0'),
            'slim-skeleton' => static::laravelAtLeast('11.0'),
            
            // Laravel 12+ features (hypothetical)
            'new-auth-system' => static::laravelAtLeast('12.0'),
            
            // Spatie Permission features
            'permission-v6' => static::packageAtLeast('spatie/laravel-permission', '6.0'),
            'permission-uuid' => static::packageAtLeast('spatie/laravel-permission', '6.0'),
            
            // Spatie Media Library features
            'media-v11' => static::packageAtLeast('spatie/laravel-medialibrary', '11.0'),
            'responsive-images' => static::hasPackage('spatie/laravel-medialibrary'),
            
            // Sanctum features
            'sanctum-v4' => static::packageAtLeast('laravel/sanctum', '4.0'),
            
            // PHP features
            'enums' => static::phpAtLeast('8.1'),
            'readonly-classes' => static::phpAtLeast('8.2'),
            'typed-class-constants' => static::phpAtLeast('8.3'),
            
            default => false,
        };
    }

    /**
     * Get version compatibility information.
     */
    public static function compatibility(): array
    {
        return [
            'oxygen' => static::oxygen(),
            'laravel' => static::laravel(),
            'laravel_supported' => version_compare(static::laravel(), static::MIN_LARAVEL_VERSION, '>=') &&
                                   version_compare(static::laravel(), static::MAX_LARAVEL_VERSION, '<='),
            'php' => static::php(),
            'packages' => [
                'sanctum' => static::packageVersion('laravel/sanctum'),
                'permission' => static::packageVersion('spatie/laravel-permission'),
                'medialibrary' => static::packageVersion('spatie/laravel-medialibrary'),
            ],
        ];
    }

    /**
     * Get main class for package detection.
     */
    protected static function getPackageClass(string $package): string
    {
        return match ($package) {
            'laravel/sanctum' => \Laravel\Sanctum\Sanctum::class,
            'spatie/laravel-permission' => \Spatie\Permission\PermissionServiceProvider::class,
            'spatie/laravel-medialibrary' => \Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
            default => $package,
        };
    }
}
