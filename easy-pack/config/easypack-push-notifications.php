<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Firebase Cloud Messaging settings here. You'll need to
    | download a service account JSON file from the Firebase console and
    | place it in a secure location.
    |
    */

    'firebase' => [
        /*
         * Path to the Firebase service account credentials JSON file.
         * You can download this from Firebase Console > Project Settings > Service Accounts
         */
        'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | General settings for push notifications.
    |
    */

    'settings' => [
        /*
         * Number of days to keep notifications before they can be purged.
         */
        'retention_days' => env('PUSH_NOTIFICATION_RETENTION_DAYS', 90),

        /*
         * Default batch size for processing pending notifications.
         */
        'batch_size' => env('PUSH_NOTIFICATION_BATCH_SIZE', 100),

        /*
         * Whether to automatically subscribe new devices to default topics.
         */
        'auto_subscribe_default_topics' => env('PUSH_AUTO_SUBSCRIBE_TOPICS', true),

        /*
         * Default TTL (time-to-live) for notifications in seconds.
         * 0 = Use FCM default (4 weeks)
         */
        'default_ttl' => env('PUSH_NOTIFICATION_TTL', 86400), // 24 hours

        /*
         * Default quiet hours (users can override in preferences).
         */
        'default_quiet_hours' => [
            'enabled' => false,
            'start' => '22:00',
            'end' => '07:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Topic Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the available topics for push notifications.
    | These topics are used for broadcasting messages to groups of devices.
    |
    */

    'topics' => [
        'all_devices' => [
            'name' => 'All Devices',
            'description' => 'Broadcasts to all registered devices',
        ],
        'ios_devices' => [
            'name' => 'iOS Devices',
            'description' => 'Broadcasts to all iOS devices',
        ],
        'android_devices' => [
            'name' => 'Android Devices',
            'description' => 'Broadcasts to all Android devices',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Categories
    |--------------------------------------------------------------------------
    |
    | Configure notification categories with their Android channel IDs,
    | default sounds, and other platform-specific settings.
    |
    */

    'categories' => [
        'general' => [
            'name' => 'General',
            'description' => 'General notifications and updates',
            'android_channel' => 'channel_general',
            'sound' => 'default',
            'importance' => 'default', // low, default, high
            'can_disable' => true,
        ],
        'promotional' => [
            'name' => 'Promotional',
            'description' => 'Offers, discounts, and marketing updates',
            'android_channel' => 'channel_promotional',
            'sound' => 'default',
            'importance' => 'low',
            'can_disable' => true,
        ],
        'transactional' => [
            'name' => 'Transactional',
            'description' => 'Orders, payments, and account activity',
            'android_channel' => 'channel_transactional',
            'sound' => 'default',
            'importance' => 'high',
            'can_disable' => false,
        ],
        'system' => [
            'name' => 'System',
            'description' => 'App updates, security alerts, and system messages',
            'android_channel' => 'channel_system',
            'sound' => 'default',
            'importance' => 'high',
            'can_disable' => false,
        ],
        'social' => [
            'name' => 'Social',
            'description' => 'Messages, follows, likes, and social activity',
            'android_channel' => 'channel_social',
            'sound' => 'default',
            'importance' => 'default',
            'can_disable' => true,
        ],
        'reminder' => [
            'name' => 'Reminders',
            'description' => 'Scheduled reminders and alerts',
            'android_channel' => 'channel_reminders',
            'sound' => 'default',
            'importance' => 'high',
            'can_disable' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Settings
    |--------------------------------------------------------------------------
    |
    | Configure how different priority levels are handled.
    |
    */

    'priorities' => [
        'low' => [
            'fcm_priority' => 'normal',
            'apns_priority' => 5,
            'bypasses_quiet_hours' => false,
        ],
        'normal' => [
            'fcm_priority' => 'normal',
            'apns_priority' => 10,
            'bypasses_quiet_hours' => false,
        ],
        'high' => [
            'fcm_priority' => 'high',
            'apns_priority' => 10,
            'bypasses_quiet_hours' => false,
        ],
        'critical' => [
            'fcm_priority' => 'high',
            'apns_priority' => 10,
            'bypasses_quiet_hours' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sound Configuration
    |--------------------------------------------------------------------------
    |
    | Available notification sounds.
    |
    */

    'sounds' => [
        'default' => 'default',
        'alert' => 'alert.wav',
        'chime' => 'chime.wav',
        'ding' => 'ding.wav',
        'none' => null,
    ],

];
