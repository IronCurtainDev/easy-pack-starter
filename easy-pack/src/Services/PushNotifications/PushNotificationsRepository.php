<?php

namespace EasyPack\Services\PushNotifications;

use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\PushNotification;
use EasyPack\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PushNotificationsRepository
{
    /**
     * Get a query builder for user notifications.
     * This allows for additional filtering before pagination.
     *
     * @param User $user
     * @return Builder
     */
    public function getQueryForUser(User $user): Builder
    {
        return PushNotification::query()
            ->where(function ($query) use ($user) {
                // Notifications sent directly to the user
                $query->where(function ($q) use ($user) {
                    $q->where('notifiable_type', User::class)
                        ->where('notifiable_id', $user->id);
                });

                // Notifications sent to user's devices
                $query->orWhere(function ($q) use ($user) {
                    $deviceIds = $user->tokens()->pluck('id')->toArray();
                    $q->where('notifiable_type', PersonalAccessToken::class)
                        ->whereIn('notifiable_id', $deviceIds);
                });

                // Topic notifications for user's subscribed topics
                $query->orWhere(function ($q) use ($user) {
                    $topics = $user->tokens()
                        ->whereNotNull('topic_subscriptions')
                        ->get()
                        ->pluck('topic_subscriptions')
                        ->flatten()
                        ->unique()
                        ->toArray();

                    if (!empty($topics)) {
                        $q->whereIn('topic', $topics);
                    }
                });
            });
    }

    /**
     * Get notifications for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getQueryForUser($user)
            ->sent()
            ->orderByDesc('sent_at')
            ->paginate($perPage);
    }

    /**
     * Get notifications for a specific device.
     *
     * @param PersonalAccessToken $token
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getForDevice(PersonalAccessToken $token, int $perPage = 15): LengthAwarePaginator
    {
        $topics = $token->topic_subscriptions ?? [];

        return PushNotification::query()
            ->where(function ($query) use ($token, $topics) {
                // Direct device notifications
                $query->where(function ($q) use ($token) {
                    $q->where('notifiable_type', PersonalAccessToken::class)
                        ->where('notifiable_id', $token->id);
                });

                // User notifications (if device belongs to a user)
                if ($token->tokenable_id) {
                    $query->orWhere(function ($q) use ($token) {
                        $q->where('notifiable_type', User::class)
                            ->where('notifiable_id', $token->tokenable_id);
                    });
                }

                // Topic notifications
                if (!empty($topics)) {
                    $query->orWhereIn('topic', $topics);
                }
            })
            ->sent()
            ->orderByDesc('sent_at')
            ->paginate($perPage);
    }

    /**
     * Get unread notifications for a user.
     *
     * @param User $user
     * @return Collection
     */
    public function getUnreadForUser(User $user): Collection
    {
        $allNotifications = $this->getForUser($user, 100)->items();

        return collect($allNotifications)->filter(function ($notification) use ($user) {
            return !$notification->status()
                ->where('user_id', $user->id)
                ->whereNotNull('read_at')
                ->exists();
        });
    }

    /**
     * Get unread count for a user.
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCountForUser(User $user): int
    {
        return $this->getUnreadForUser($user)->count();
    }

    /**
     * Mark notification as read for a user.
     *
     * @param PushNotification $notification
     * @param User $user
     * @return void
     */
    public function markAsRead(PushNotification $notification, User $user): void
    {
        $notification->markAsReadByUser($user);
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param User $user
     * @return int Number of notifications marked
     */
    public function markAllAsRead(User $user): int
    {
        $unread = $this->getUnreadForUser($user);

        foreach ($unread as $notification) {
            $this->markAsRead($notification, $user);
        }

        return $unread->count();
    }

    /**
     * Get pending notifications.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPending(int $limit = 100): Collection
    {
        return PushNotification::pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Find notification by UUID.
     *
     * @param string $uuid
     * @return PushNotification|null
     */
    public function findByUuid(string $uuid): ?PushNotification
    {
        return PushNotification::where('uuid', $uuid)->first();
    }

    /**
     * Get topic notifications.
     *
     * @param string $topic
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTopic(string $topic, int $perPage = 15): LengthAwarePaginator
    {
        return PushNotification::forTopic($topic)
            ->sent()
            ->orderByDesc('sent_at')
            ->paginate($perPage);
    }

    /**
     * Delete old notifications.
     *
     * @param int $daysOld
     * @return int Number deleted
     */
    public function deleteOld(int $daysOld = 90): int
    {
        return PushNotification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get notification statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => PushNotification::count(),
            'pending' => PushNotification::pending()->count(),
            'sent' => PushNotification::sent()->count(),
            'sent_today' => PushNotification::sent()
                ->whereDate('sent_at', today())
                ->count(),
            'by_topic' => PushNotification::whereNotNull('topic')
                ->selectRaw('topic, count(*) as count')
                ->groupBy('topic')
                ->pluck('count', 'topic')
                ->toArray(),
        ];
    }

    /**
     * Create a notification for a user.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return PushNotification
     */
    public function createForUser(User $user, string $title, string $body, array $data = []): PushNotification
    {
        return PushNotification::forUser($user, $title, $body, $data);
    }

    /**
     * Create a notification for a device.
     *
     * @param PersonalAccessToken $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return PushNotification
     */
    public function createForDevice(PersonalAccessToken $token, string $title, string $body, array $data = []): PushNotification
    {
        return PushNotification::forDevice($token, $title, $body, $data);
    }

    /**
     * Create a topic notification.
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return PushNotification
     */
    public function createForTopic(string $topic, string $title, string $body, array $data = []): PushNotification
    {
        return PushNotification::forTopic($topic, $title, $body, $data);
    }
}
