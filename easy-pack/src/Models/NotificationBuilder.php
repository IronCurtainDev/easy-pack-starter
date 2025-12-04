<?php

namespace EasyPack\Models;

use EasyPack\Services\PushNotifications\NotificationCategory;
use EasyPack\Services\PushNotifications\NotificationPriority;

class NotificationBuilder
{
    protected string $title = '';
    protected string $message = '';
    protected array $data = [];
    protected ?string $topic = null;
    protected array $tokens = [];
    protected ?string $scheduledAt = null;
    protected string $category;
    protected string $priority;

    public function __construct()
    {
        $this->category = NotificationCategory::GENERAL;
        $this->priority = NotificationPriority::NORMAL;
    }

    /**
     * Create a new notification builder instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the notification title.
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the notification message.
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set additional data payload.
     */
    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Add a data item.
     */
    public function addData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Set the topic for topic-based sending.
     */
    public function toTopic(string $topic): self
    {
        $this->topic = $topic;
        $this->tokens = []; // Clear tokens when sending to topic
        return $this;
    }

    /**
     * Set the target device tokens.
     */
    public function toTokens(array $tokens): self
    {
        $this->tokens = $tokens;
        $this->topic = null; // Clear topic when sending to tokens
        return $this;
    }

    /**
     * Add a device token.
     */
    public function addToken(string $token): self
    {
        $this->tokens[] = $token;
        $this->topic = null;
        return $this;
    }

    /**
     * Send to a user (all their devices).
     */
    public function toUser(User $user): self
    {
        $tokens = $user->tokens()
            ->whereNotNull('device_push_token')
            ->pluck('device_push_token')
            ->toArray();

        return $this->toTokens($tokens);
    }

    /**
     * Send to multiple users.
     */
    public function toUsers($users): self
    {
        $tokens = [];

        foreach ($users as $user) {
            $userTokens = $user->tokens()
                ->whereNotNull('device_push_token')
                ->pluck('device_push_token')
                ->toArray();

            $tokens = array_merge($tokens, $userTokens);
        }

        return $this->toTokens(array_unique($tokens));
    }

    /**
     * Set the scheduled time.
     */
    public function scheduledAt(string $dateTime): self
    {
        $this->scheduledAt = $dateTime;
        return $this;
    }

    /**
     * Schedule to be sent after a delay.
     */
    public function delay(int $minutes): self
    {
        $this->scheduledAt = now()->addMinutes($minutes)->toDateTimeString();
        return $this;
    }

    /**
     * Set the notification category.
     */
    public function category(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Set the notification priority.
     */
    public function priority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set as high priority.
     */
    public function highPriority(): self
    {
        $this->priority = NotificationPriority::HIGH;
        return $this;
    }

    /**
     * Set as low priority.
     */
    public function lowPriority(): self
    {
        $this->priority = NotificationPriority::LOW;
        return $this;
    }

    /**
     * Build and save the notification to database.
     */
    public function save(): PushNotification
    {
        return PushNotification::create([
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'topic' => $this->topic,
            'tokens' => $this->tokens,
            'status' => PushNotification::STATUS_PENDING,
            'scheduled_at' => $this->scheduledAt,
            'category' => $this->category,
            'priority' => $this->priority,
        ]);
    }

    /**
     * Build notification data array without saving.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'topic' => $this->topic,
            'tokens' => $this->tokens,
            'scheduled_at' => $this->scheduledAt,
            'category' => $this->category,
            'priority' => $this->priority,
        ];
    }

    /**
     * Validate the notification is ready to send.
     */
    public function isValid(): bool
    {
        if (empty($this->title) || empty($this->message)) {
            return false;
        }

        if (empty($this->topic) && empty($this->tokens)) {
            return false;
        }

        return true;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Title is required';
        }

        if (empty($this->message)) {
            $errors[] = 'Message is required';
        }

        if (empty($this->topic) && empty($this->tokens)) {
            $errors[] = 'Either topic or tokens must be specified';
        }

        return $errors;
    }
}
