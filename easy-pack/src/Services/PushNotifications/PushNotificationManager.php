<?php

namespace EasyPack\Services\PushNotifications;

use EasyPack\Models\NotificationPreference;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\PushNotification;
use EasyPack\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationManager
{
    /**
     * Firebase messaging instance.
     */
    protected $messaging;

    /**
     * Whether Firebase is configured.
     */
    protected bool $firebaseConfigured = false;

    /**
     * Create a new PushNotificationManager instance.
     */
    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Initialize Firebase messaging.
     */
    protected function initializeFirebase(): void
    {
        $credentialsPath = config('easypack-push-notifications.firebase.credentials');

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();
                $this->firebaseConfigured = true;
            } catch (\Exception $e) {
                Log::error('Failed to initialize Firebase: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if Firebase is configured.
     */
    public function isConfigured(): bool
    {
        return $this->firebaseConfigured;
    }

    /**
     * Check if notification should be delivered based on user preferences.
     */
    public function shouldDeliverToUser(User $user, PushNotification $notification): bool
    {
        $preferences = NotificationPreference::forUser($user);
        return $preferences->shouldDeliver($notification->category, $notification->priority);
    }

    /**
     * Build Firebase CloudMessage from PushNotification model.
     */
    protected function buildMessage(PushNotification $notification, string $target, string $targetType = 'token'): CloudMessage
    {
        $message = CloudMessage::withTarget($targetType, $target);

        // Add notification payload (unless silent)
        if (!$notification->is_silent) {
            $notificationPayload = Notification::create(
                $notification->title,
                $notification->message
            );

            if ($notification->image_url) {
                $notificationPayload = $notificationPayload->withImageUrl($notification->image_url);
            }

            $message = $message->withNotification($notificationPayload);
        }

        // Build data payload
        $data = $notification->data ?? [];

        // Add action URL to data
        if ($notification->action_url) {
            $data['action_url'] = $notification->action_url;
        }

        // Add action buttons to data
        if ($notification->action_buttons) {
            $data['action_buttons'] = json_encode($notification->action_buttons);
        }

        // Add notification UUID for tracking
        $data['notification_uuid'] = $notification->uuid;
        $data['category'] = $notification->category;

        if (!empty($data)) {
            // FCM requires all data values to be strings
            $message = $message->withData(array_map('strval', array_filter($data)));
        }

        // Build APNS (iOS) config
        $message = $message->withApnsConfig($this->buildApnsConfig($notification));

        // Build Android config
        $message = $message->withAndroidConfig($this->buildAndroidConfig($notification));

        return $message;
    }

    /**
     * Build APNS configuration for iOS.
     */
    protected function buildApnsConfig(PushNotification $notification): ApnsConfig
    {
        $payload = [
            'aps' => [],
        ];

        // Silent notification (content-available)
        if ($notification->is_silent) {
            $payload['aps']['content-available'] = 1;
        } else {
            // Badge count
            if ($notification->badge_count !== null) {
                $payload['aps']['badge'] = $notification->badge_count;
            }

            // Sound
            $sound = $notification->sound ?? config("easypack-push-notifications.categories.{$notification->category}.sound", 'default');
            if ($sound) {
                $payload['aps']['sound'] = $sound;
            }

            // Mutable content for rich notifications
            if ($notification->image_url || $notification->action_buttons) {
                $payload['aps']['mutable-content'] = 1;
            }

            // Category for action buttons
            if ($notification->action_buttons) {
                $payload['aps']['category'] = $notification->category;
            }
        }

        // Thread ID for grouping
        if ($notification->collapse_key) {
            $payload['aps']['thread-id'] = $notification->collapse_key;
        }

        // Custom APNS config from notification
        $customApns = $notification->apns_config;
        if (!empty($customApns)) {
            $payload = array_merge_recursive($payload, $customApns);
        }

        $config = ApnsConfig::fromArray([
            'payload' => $payload,
            'headers' => [
                'apns-priority' => (string) NotificationPriority::apnsPriority($notification->priority),
            ],
        ]);

        if ($notification->ttl) {
            $config = $config->withHeader('apns-expiration', (string) (time() + $notification->ttl));
        }

        return $config;
    }

    /**
     * Build Android configuration.
     */
    protected function buildAndroidConfig(PushNotification $notification): AndroidConfig
    {
        $config = [
            'priority' => NotificationPriority::fcmPriority($notification->priority),
        ];

        // TTL
        if ($notification->ttl) {
            $config['ttl'] = $notification->ttl . 's';
        }

        // Collapse key
        if ($notification->collapse_key) {
            $config['collapse_key'] = $notification->collapse_key;
        }

        // Notification config (unless silent)
        if (!$notification->is_silent) {
            $androidNotification = [];

            // Channel ID
            $channelId = $notification->channel_id
                ?? NotificationCategory::androidChannelId($notification->category);
            if ($channelId) {
                $androidNotification['channel_id'] = $channelId;
            }

            // Sound
            $sound = $notification->sound ?? config("easypack-push-notifications.categories.{$notification->category}.sound");
            if ($sound && $sound !== 'default') {
                $androidNotification['sound'] = $sound;
            }

            // Image
            if ($notification->image_url) {
                $androidNotification['image'] = $notification->image_url;
            }

            // Click action
            if ($notification->action_url) {
                $androidNotification['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
            }

            if (!empty($androidNotification)) {
                $config['notification'] = $androidNotification;
            }
        }

        // Custom Android config from notification
        $customAndroid = $notification->android_config;
        if (!empty($customAndroid)) {
            $config = array_merge_recursive($config, $customAndroid);
        }

        return AndroidConfig::fromArray($config);
    }

    /**
     * Send a push notification to a specific device.
     */
    public function sendToDevice(PersonalAccessToken $token, string $title, string $body, array $data = [], array $options = []): bool
    {
        if (!$token->device_push_token) {
            return false;
        }

        if (!$this->firebaseConfigured) {
            Log::warning('Firebase not configured. Notification queued but not sent.');
            return false;
        }

        try {
            // Create a temporary notification for building message
            $notification = new PushNotification(array_merge([
                'title' => $title,
                'message' => $body,
                'data' => $data,
                'uuid' => \Illuminate\Support\Str::uuid(),
            ], $options));

            $message = $this->buildMessage($notification, $token->device_push_token, 'token');
            $this->messaging->send($message);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage(), [
                'token_id' => $token->id,
                'device_id' => $token->device_id,
            ]);
            return false;
        }
    }

    /**
     * Send a silent/data-only notification to a device.
     */
    public function sendSilentToDevice(PersonalAccessToken $token, array $data = []): bool
    {
        return $this->sendToDevice($token, '', '', $data, ['is_silent' => true]);
    }

    /**
     * Send a push notification to all user's devices.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], array $options = []): int
    {
        $tokens = $user->tokens()
            ->whereNotNull('device_push_token')
            ->active()
            ->get();

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToDevice($token, $title, $body, $data, $options)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send a silent notification to all user's devices.
     */
    public function sendSilentToUser(User $user, array $data = []): int
    {
        return $this->sendToUser($user, '', '', $data, ['is_silent' => true]);
    }

    /**
     * Send a push notification to a topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], array $options = []): bool
    {
        if (!$this->firebaseConfigured) {
            Log::warning('Firebase not configured. Topic notification not sent.', ['topic' => $topic]);
            return false;
        }

        try {
            $notification = new PushNotification(array_merge([
                'title' => $title,
                'message' => $body,
                'data' => $data,
                'topic' => $topic,
                'uuid' => \Illuminate\Support\Str::uuid(),
            ], $options));

            $message = $this->buildMessage($notification, $topic, 'topic');
            $this->messaging->send($message);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send topic notification: ' . $e->getMessage(), [
                'topic' => $topic,
            ]);
            return false;
        }
    }

    /**
     * Send a silent notification to a topic.
     */
    public function sendSilentToTopic(string $topic, array $data = []): bool
    {
        return $this->sendToTopic($topic, '', '', $data, ['is_silent' => true]);
    }

    /**
     * Send multiple notifications in batch.
     *
     * @param array $tokens Push tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array Results
     */
    public function sendToMultiple(array $tokens, string $title, string $body, array $data = []): array
    {
        if (!$this->firebaseConfigured || empty($tokens)) {
            return ['success' => 0, 'failed' => count($tokens)];
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $report = $this->messaging->sendMulticast($message, $tokens);

            return [
                'success' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send multicast notification: ' . $e->getMessage());
            return ['success' => 0, 'failed' => count($tokens)];
        }
    }

    /**
     * Subscribe a device to a topic.
     *
     * @param PersonalAccessToken $token
     * @param string $topic
     * @return bool
     */
    public function subscribeToTopic(PersonalAccessToken $token, string $topic): bool
    {
        if (!$token->device_push_token) {
            return false;
        }

        if (!$this->firebaseConfigured) {
            Log::warning('Firebase not configured. Topic subscription not completed.', ['topic' => $topic]);
            // Still update local subscription status
            $this->updateLocalSubscription($token, $topic, true);
            return true;
        }

        try {
            $this->messaging->subscribeToTopic($topic, [$token->device_push_token]);
            $this->updateLocalSubscription($token, $topic, true);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to topic: ' . $e->getMessage(), [
                'token_id' => $token->id,
                'topic' => $topic,
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe a device from a topic.
     *
     * @param PersonalAccessToken $token
     * @param string $topic
     * @return bool
     */
    public function unsubscribeFromTopic(PersonalAccessToken $token, string $topic): bool
    {
        if (!$token->device_push_token) {
            return false;
        }

        if (!$this->firebaseConfigured) {
            Log::warning('Firebase not configured. Topic unsubscription not completed.', ['topic' => $topic]);
            $this->updateLocalSubscription($token, $topic, false);
            return true;
        }

        try {
            $this->messaging->unsubscribeFromTopic($topic, [$token->device_push_token]);
            $this->updateLocalSubscription($token, $topic, false);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from topic: ' . $e->getMessage(), [
                'token_id' => $token->id,
                'topic' => $topic,
            ]);
            return false;
        }
    }

    /**
     * Subscribe device to default topics based on device type.
     *
     * @param PersonalAccessToken $token
     * @return void
     */
    public function subscribeToDefaultTopics(PersonalAccessToken $token): void
    {
        if (!$token->device_push_token) {
            return;
        }

        // Subscribe to all devices topic
        $this->subscribeToTopic($token, PushNotificationTopic::TOPIC_ALL_DEVICES);

        // Subscribe to device type specific topic
        $typeTopic = PushNotificationTopic::forDeviceType($token->device_type ?? '');
        if ($typeTopic) {
            $this->subscribeToTopic($token, $typeTopic);
        }
    }

    /**
     * Unsubscribe device from all topics.
     *
     * @param PersonalAccessToken $token
     * @return void
     */
    public function unsubscribeFromAllTopics(PersonalAccessToken $token): void
    {
        if (!$token->device_push_token) {
            return;
        }

        foreach (array_keys(PushNotificationTopic::all()) as $topic) {
            $this->unsubscribeFromTopic($token, $topic);
        }

        // Clear local subscriptions
        $token->update(['topic_subscriptions' => []]);
    }

    /**
     * Update local subscription status.
     *
     * @param PersonalAccessToken $token
     * @param string $topic
     * @param bool $subscribed
     * @return void
     */
    protected function updateLocalSubscription(PersonalAccessToken $token, string $topic, bool $subscribed): void
    {
        $subscriptions = $token->topic_subscriptions ?? [];

        if ($subscribed) {
            if (!in_array($topic, $subscriptions)) {
                $subscriptions[] = $topic;
            }
        } else {
            $subscriptions = array_values(array_diff($subscriptions, [$topic]));
        }

        $token->update(['topic_subscriptions' => $subscriptions]);
    }

    /**
     * Create and queue a notification for later sending.
     *
     * @param User|PersonalAccessToken $notifiable
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $topic
     * @return PushNotification
     */
    public function queue($notifiable, string $title, string $body, array $data = [], ?string $topic = null): PushNotification
    {
        if ($notifiable instanceof User) {
            return PushNotification::forUser($notifiable, $title, $body, $data);
        }

        if ($notifiable instanceof PersonalAccessToken) {
            return PushNotification::forDevice($notifiable, $title, $body, $data);
        }

        if ($topic) {
            return PushNotification::forTopic($topic, $title, $body, $data);
        }

        throw new \InvalidArgumentException('Invalid notifiable type');
    }

    /**
     * Process pending notifications.
     *
     * @param int $limit
     * @return array
     */
    public function processPending(int $limit = 100): array
    {
        $notifications = PushNotification::pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($notifications as $notification) {
            $results['processed']++;

            $success = $this->processNotification($notification);

            if ($success) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Process a single notification.
     *
     * @param PushNotification $notification
     * @return bool
     */
    public function processNotification(PushNotification $notification): bool
    {
        // Topic notification
        if ($notification->topic) {
            $success = $this->sendTopicNotification($notification);

            if ($success) {
                $notification->markAsSent();
            }

            return $success;
        }

        // User notification
        if ($notification->notifiable_type === User::class) {
            $user = User::find($notification->notifiable_id);
            if (!$user) {
                $notification->delete();
                return false;
            }

            // Check user preferences
            if (!$this->shouldDeliverToUser($user, $notification)) {
                Log::info('Notification blocked by user preferences', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'category' => $notification->category,
                ]);
                // Don't delete, mark as sent but not delivered
                $notification->markAsSent();
                return false;
            }

            $sent = $this->sendUserNotification($user, $notification);

            if ($sent > 0) {
                $notification->markAsSent();
            }

            return $sent > 0;
        }

        // Device notification
        if ($notification->notifiable_type === PersonalAccessToken::class) {
            $token = PersonalAccessToken::find($notification->notifiable_id);
            if (!$token || !$token->device_push_token) {
                $notification->delete();
                return false;
            }

            // Check user preferences if token belongs to a user
            if ($token->tokenable_id && $token->tokenable_type === User::class) {
                $user = User::find($token->tokenable_id);
                if ($user && !$this->shouldDeliverToUser($user, $notification)) {
                    $notification->markAsSent();
                    return false;
                }
            }

            $success = $this->sendDeviceNotification($token, $notification);

            if ($success) {
                $notification->markAsSent();
            }

            return $success;
        }

        return false;
    }

    /**
     * Send notification to a topic using PushNotification model.
     */
    protected function sendTopicNotification(PushNotification $notification): bool
    {
        if (!$this->firebaseConfigured) {
            return false;
        }

        try {
            $message = $this->buildMessage($notification, $notification->topic, 'topic');
            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send topic notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'topic' => $notification->topic,
            ]);
            return false;
        }
    }

    /**
     * Send notification to a user using PushNotification model.
     */
    protected function sendUserNotification(User $user, PushNotification $notification): int
    {
        $tokens = $user->tokens()
            ->whereNotNull('device_push_token')
            ->active()
            ->get();

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendDeviceNotification($token, $notification)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send notification to a device using PushNotification model.
     */
    protected function sendDeviceNotification(PersonalAccessToken $token, PushNotification $notification): bool
    {
        if (!$token->device_push_token || !$this->firebaseConfigured) {
            return false;
        }

        try {
            $message = $this->buildMessage($notification, $token->device_push_token, 'token');
            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send device notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'token_id' => $token->id,
            ]);
            return false;
        }
    }

    /**
     * Get notification statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'pending' => PushNotification::pending()->count(),
            'sent_today' => PushNotification::sent()
                ->whereDate('sent_at', today())
                ->count(),
            'total_sent' => PushNotification::sent()->count(),
            'by_category' => PushNotification::sent()
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'by_priority' => PushNotification::sent()
                ->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'silent_count' => PushNotification::sent()->where('is_silent', true)->count(),
            'firebase_configured' => $this->firebaseConfigured,
        ];
    }
}
