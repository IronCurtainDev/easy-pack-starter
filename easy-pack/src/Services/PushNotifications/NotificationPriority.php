<?php

namespace EasyPack\Services\PushNotifications;

/**
 * Notification priority constants and helpers.
 */
class NotificationPriority
{
    /**
     * Low priority - can be delayed, batched.
     * FCM: normal priority
     * APNS: power considerations apply
     */
    public const LOW = 'low';

    /**
     * Normal priority - default.
     * FCM: normal priority
     * APNS: sent immediately when possible
     */
    public const NORMAL = 'normal';

    /**
     * High priority - delivered immediately.
     * FCM: high priority
     * APNS: sent immediately
     */
    public const HIGH = 'high';

    /**
     * Critical priority - bypasses DND, delivered immediately.
     * FCM: high priority
     * APNS: critical alert (requires entitlement)
     */
    public const CRITICAL = 'critical';

    /**
     * Get all priorities with their FCM/APNS mappings.
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        return [
            self::LOW => [
                'name' => 'Low',
                'description' => 'Non-urgent, can be delayed',
                'fcm_priority' => 'normal',
                'apns_priority' => 5,
                'bypasses_quiet_hours' => false,
            ],
            self::NORMAL => [
                'name' => 'Normal',
                'description' => 'Standard delivery',
                'fcm_priority' => 'normal',
                'apns_priority' => 10,
                'bypasses_quiet_hours' => false,
            ],
            self::HIGH => [
                'name' => 'High',
                'description' => 'Immediate delivery',
                'fcm_priority' => 'high',
                'apns_priority' => 10,
                'bypasses_quiet_hours' => false,
            ],
            self::CRITICAL => [
                'name' => 'Critical',
                'description' => 'Urgent, bypasses quiet hours',
                'fcm_priority' => 'high',
                'apns_priority' => 10,
                'bypasses_quiet_hours' => true,
            ],
        ];
    }

    /**
     * Get all priority names.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * Check if a priority is valid.
     *
     * @param string $priority
     * @return bool
     */
    public static function isValid(string $priority): bool
    {
        return in_array($priority, self::names());
    }

    /**
     * Check if priority bypasses quiet hours.
     *
     * @param string $priority
     * @return bool
     */
    public static function bypassesQuietHours(string $priority): bool
    {
        return self::all()[$priority]['bypasses_quiet_hours'] ?? false;
    }

    /**
     * Get FCM priority string.
     *
     * @param string $priority
     * @return string
     */
    public static function fcmPriority(string $priority): string
    {
        return self::all()[$priority]['fcm_priority'] ?? 'normal';
    }

    /**
     * Get APNS priority (5 or 10).
     *
     * @param string $priority
     * @return int
     */
    public static function apnsPriority(string $priority): int
    {
        return self::all()[$priority]['apns_priority'] ?? 10;
    }
}
