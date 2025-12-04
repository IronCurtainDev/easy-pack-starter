<?php

namespace EasyPack\Services\PushNotifications;

/**
 * Firebase Cloud Messaging topic constants.
 */
class PushNotificationTopic
{
    /**
     * Topic for all devices (broadcast to everyone).
     */
    public const TOPIC_ALL_DEVICES = 'all_devices';

    /**
     * Topic for all iOS devices.
     */
    public const TOPIC_IOS_DEVICES = 'ios_devices';

    /**
     * Topic for all Android devices.
     */
    public const TOPIC_ANDROID_DEVICES = 'android_devices';

    /**
     * Get all available topics.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::TOPIC_ALL_DEVICES => 'All Devices',
            self::TOPIC_IOS_DEVICES => 'All iOS Devices',
            self::TOPIC_ANDROID_DEVICES => 'All Android Devices',
        ];
    }

    /**
     * Get topic for a device type.
     *
     * @param string $deviceType
     * @return string|null
     */
    public static function forDeviceType(string $deviceType): ?string
    {
        return match (strtolower($deviceType)) {
            'apple', 'ios' => self::TOPIC_IOS_DEVICES,
            'android' => self::TOPIC_ANDROID_DEVICES,
            default => null,
        };
    }

    /**
     * Check if a topic name is valid.
     *
     * @param string $topic
     * @return bool
     */
    public static function isValid(string $topic): bool
    {
        return in_array($topic, [
            self::TOPIC_ALL_DEVICES,
            self::TOPIC_IOS_DEVICES,
            self::TOPIC_ANDROID_DEVICES,
        ]);
    }
}
