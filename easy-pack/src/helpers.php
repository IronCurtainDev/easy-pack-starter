<?php

use EasyPack\Facades\Setting;
use EasyPack\Services\UIHelper;
use EasyPack\Support\Version;

if (!function_exists('setting')) {
    /**
     * Helper function for the setting facade - get a setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = ''): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setting_set')) {
    /**
     * Helper function to set or update a setting.
     *
     * @param string $key
     * @param string|null $value
     * @param string|null $dataType
     * @param string|null $description
     * @return \EasyPack\Models\Setting
     */
    function setting_set(string $key, ?string $value = null, ?string $dataType = null, ?string $description = null)
    {
        return Setting::setOrUpdate($key, $value, $dataType, $description);
    }
}

if (!function_exists('setting_forget')) {
    /**
     * Delete a setting.
     *
     * @param string $key
     * @return void
     */
    function setting_forget(string $key): void
    {
        Setting::forget($key);
    }
}

if (!function_exists('ui')) {
    /**
     * Get the UI helper instance.
     *
     * @return UIHelper
     */
    function ui(): UIHelper
    {
        return app(UIHelper::class);
    }
}

if (!function_exists('standard_date')) {
    /**
     * Format a date in standard format.
     *
     * @param \DateTimeInterface|string|null $date
     * @param string $format
     * @return string
     */
    function standard_date($date, string $format = 'M j, Y'): string
    {
        if (empty($date)) {
            return '';
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        return $date->format($format);
    }
}

if (!function_exists('has_feature')) {
    /**
     * Check if a feature is enabled in config.
     * Supports dot notation for nested features.
     *
     * @param string $feature Dot notation path (e.g., 'auth.public_users_can_register')
     * @param bool $default Default value if feature not found
     * @return bool
     *
     * @example
     * has_feature('auth.public_users_can_register')  // Check nested feature
     * has_feature('api.active')                       // Check API status
     * has_feature('notifications.push_enabled')       // Check notifications
     */
    function has_feature(string $feature, bool $default = false): bool
    {
        return (bool) config("features.{$feature}", $default);
    }
}

if (!function_exists('has_module')) {
    /**
     * Check if an Oxygen module is enabled.
     *
     * @param string $module Module name (e.g., 'push_notifications', 'media_management')
     * @param bool $default Default value if module not found
     * @return bool
     *
     * @example
     * has_module('push_notifications')  // Check if push notifications module is enabled
     * has_module('device_management')   // Check if device management module is enabled
     */
    function has_module(string $module, bool $default = true): bool
    {
        return (bool) config("oxygen.modules.{$module}", $default);
    }
}

if (!function_exists('oxygen_config')) {
    /**
     * Get an Oxygen configuration value.
     *
     * @param string|null $key Configuration key (dot notation supported)
     * @param mixed $default Default value
     * @return mixed
     *
     * @example
     * oxygen_config('api_prefix')           // Get API prefix
     * oxygen_config('tokens.expiry_days')   // Get token expiry
     * oxygen_config()                       // Get all oxygen config
     */
    function oxygen_config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return config('easypack');
        }

        return config("oxygen.{$key}", $default);
    }
}

if (!function_exists('is_admin_panel_enabled')) {
    /**
     * Check if the admin panel is enabled.
     *
     * @return bool
     */
    function is_admin_panel_enabled(): bool
    {
        return (bool) config('easypack.admin_panel_enabled', true);
    }
}

if (!function_exists('oxygen_api_prefix')) {
    /**
     * Get the configured API prefix.
     *
     * @return string
     *
     * @example
     * oxygen_api_prefix()  // Returns 'api/v1' by default
     */
    function oxygen_api_prefix(): string
    {
        return config('easypack.api_prefix', 'api/v1');
    }
}

if (!function_exists('oxygen_admin_prefix')) {
    /**
     * Get the configured admin panel prefix.
     *
     * @return string
     *
     * @example
     * oxygen_admin_prefix()  // Returns 'manage' by default
     */
    function oxygen_admin_prefix(): string
    {
        return config('easypack.admin_prefix', 'manage');
    }
}

// ============================================
// VERSION & COMPATIBILITY HELPERS
// ============================================

if (!function_exists('oxygen_version')) {
    /**
     * Get the Easy Pack package version.
     *
     * @return string
     *
     * @example
     * oxygen_version()  // Returns '1.0.0'
     */
    function oxygen_version(): string
    {
        return Version::oxygen();
    }
}

if (!function_exists('oxygen_laravel_version')) {
    /**
     * Get the current Laravel framework version.
     *
     * @return string
     *
     * @example
     * oxygen_laravel_version()  // Returns '12.0.0'
     */
    function oxygen_laravel_version(): string
    {
        return Version::laravel();
    }
}

if (!function_exists('oxygen_laravel_major')) {
    /**
     * Get the major Laravel version number.
     *
     * @return int
     *
     * @example
     * oxygen_laravel_major()  // Returns 12
     */
    function oxygen_laravel_major(): int
    {
        return Version::laravelMajor();
    }
}

if (!function_exists('oxygen_supports')) {
    /**
     * Check if a feature is supported in the current environment.
     * 
     * Useful for conditional logic when APIs differ between versions.
     *
     * @param string $feature Feature name to check
     * @return bool
     *
     * @example
     * oxygen_supports('typed-casts')       // Laravel 11+ feature
     * oxygen_supports('permission-v6')     // Spatie Permission v6 feature
     * oxygen_supports('media-v11')         // Spatie Media Library v11 feature
     * oxygen_supports('sanctum-v4')        // Laravel Sanctum v4 feature
     * oxygen_supports('enums')             // PHP 8.1+ feature
     */
    function oxygen_supports(string $feature): bool
    {
        return Version::supports($feature);
    }
}

if (!function_exists('oxygen_is_laravel')) {
    /**
     * Check if Laravel is a specific major version.
     *
     * @param int $majorVersion Major version number (e.g., 11, 12, 13)
     * @return bool
     *
     * @example
     * oxygen_is_laravel(12)  // Returns true if running Laravel 12.x
     * oxygen_is_laravel(11)  // Returns true if running Laravel 11.x
     */
    function oxygen_is_laravel(int $majorVersion): bool
    {
        return Version::isLaravel($majorVersion);
    }
}

if (!function_exists('oxygen_laravel_at_least')) {
    /**
     * Check if current Laravel version is at least the specified version.
     *
     * @param string $version Version string (e.g., '11.0', '12.5')
     * @return bool
     *
     * @example
     * oxygen_laravel_at_least('12.0')  // Returns true if Laravel >= 12.0
     * oxygen_laravel_at_least('11.5')  // Returns true if Laravel >= 11.5
     */
    function oxygen_laravel_at_least(string $version): bool
    {
        return Version::laravelAtLeast($version);
    }
}

if (!function_exists('oxygen_package_version')) {
    /**
     * Get a package's installed version.
     *
     * @param string $package Package name (e.g., 'laravel/sanctum')
     * @return string|null Version string or null if not installed
     *
     * @example
     * oxygen_package_version('laravel/sanctum')           // Returns '4.0.0'
     * oxygen_package_version('spatie/laravel-permission') // Returns '6.0.0'
     */
    function oxygen_package_version(string $package): ?string
    {
        return Version::packageVersion($package);
    }
}

if (!function_exists('oxygen_compatibility')) {
    /**
     * Get full version compatibility information.
     *
     * @return array
     *
     * @example
     * oxygen_compatibility()
     * // Returns:
     * // [
     * //     'oxygen' => '1.0.0',
     * //     'laravel' => '12.0.0',
     * //     'laravel_supported' => true,
     * //     'php' => '8.2.0',
     * //     'packages' => [
     * //         'sanctum' => '4.0.0',
     * //         'permission' => '6.0.0',
     * //         'medialibrary' => '11.0.0',
     * //     ],
     * // ]
     */
    function oxygen_compatibility(): array
    {
        return Version::compatibility();
    }
}
