<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\NotificationPreference;
use EasyPack\Services\PushNotifications\NotificationCategory;
use EasyPack\Services\PushNotifications\NotificationPriority;
use EasyPack\Services\PushNotifications\PushNotificationManager;
use EasyPack\Services\PushNotifications\PushNotificationsRepository;
use EasyPack\Services\PushNotifications\PushNotificationTopic;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushNotificationsController extends Controller
{
    public function __construct(
        protected PushNotificationManager $manager,
        protected PushNotificationsRepository $repository
    ) {}

    /**
     * Get list of notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('List Notifications')
                ->setDescription('Get a paginated list of notifications for the authenticated user')
                ->setParams([
                    (new Param('per_page', ParamType::INTEGER, 'Items per page (max 50)'))->optional()->setDefaultValue(15),
                    (new Param('category', ParamType::STRING, 'Filter by notification category'))->optional(),
                    (new Param('is_read', ParamType::BOOLEAN, 'Filter by read status'))->optional(),
                    (new Param('priority', ParamType::STRING, 'Filter by priority'))->optional(),
                    (new Param('include_silent', ParamType::BOOLEAN, 'Include silent notifications'))->optional()->setDefaultValue(false),
                ])
                ->setSuccessMessageOnly();
        });

        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        // Build query with filters
        $query = $this->repository->getQueryForUser($user);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by read status
        if ($request->has('is_read')) {
            if ($request->boolean('is_read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Exclude silent notifications by default (unless requested)
        if (!$request->boolean('include_silent', false)) {
            $query->where('is_silent', false);
        }

        $notifications = $query->sent()
            ->orderByDesc('sent_at')
            ->paginate(min($perPage, 50));

        return response()->apiSuccess([
            'notifications' => collect($notifications->items())->map->toApiArray(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $this->repository->getUnreadCountForUser($user),
        ]);
    }

    /**
     * Get a single notification by UUID.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Notification')
                ->setDescription('Get a single notification by UUID')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The notification UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $notification = $this->repository->findByUuid($uuid);

        if (!$notification) {
            return response()->apiNotFound('Notification not found.');
        }

        return response()->apiSuccess([
            'notification' => $notification->toApiArray(),
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Delete Notification')
                ->setDescription('Delete a notification')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The notification UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $notification = $this->repository->findByUuid($uuid);

        if (!$notification) {
            return response()->apiNotFound('Notification not found.');
        }

        $notification->delete();

        return response()->apiSuccess(null, 'Notification deleted.');
    }

    /**
     * Get notifications for the current device.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forDevice(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Device Notifications')
                ->setDescription('Get notifications for the current device')
                ->setParams([
                    (new Param('per_page', ParamType::INTEGER, 'Items per page (max 50)'))->optional()->setDefaultValue(15),
                ])
                ->setSuccessMessageOnly();
        });

        $token = $request->user()->currentAccessToken();
        $perPage = $request->input('per_page', 15);

        $notifications = $this->repository->getForDevice($token, min($perPage, 50));

        return response()->apiSuccess([
            'notifications' => collect($notifications->items())->map->toApiArray(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Mark as Read')
                ->setDescription('Mark a notification as read')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The notification UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $notification = $this->repository->findByUuid($uuid);

        if (!$notification) {
            return response()->apiNotFound('Notification not found.');
        }

        $this->repository->markAsRead($notification, $request->user());

        return response()->apiSuccess(null, 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Mark All as Read')
                ->setDescription('Mark all notifications as read')
                ->setSuccessMessageOnly();
        });

        $count = $this->repository->markAllAsRead($request->user());

        return response()->apiSuccess([
            'count' => $count,
        ], "{$count} notifications marked as read.");
    }

    /**
     * Get available notification categories.
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Categories')
                ->setDescription('Get available notification categories')
                ->setSuccessMessageOnly();
        });

        return response()->apiSuccess([
            'categories' => NotificationCategory::all(),
        ]);
    }

    /**
     * Get available notification priorities.
     *
     * @return JsonResponse
     */
    public function priorities(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Priorities')
                ->setDescription('Get available notification priorities')
                ->setSuccessMessageOnly();
        });

        return response()->apiSuccess([
            'priorities' => NotificationPriority::all(),
        ]);
    }

    /**
     * Get user notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPreferences(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Preferences')
                ->setDescription('Get user notification preferences')
                ->setSuccessMessageOnly();
        });

        $preferences = NotificationPreference::forUser($request->user());

        return response()->apiSuccess([
            'preferences' => $preferences->toApiArray(),
            'available_categories' => NotificationCategory::all(),
        ]);
    }

    /**
     * Update user notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Update Preferences')
                ->setDescription('Update user notification preferences')
                ->setParams([
                    (new Param('notifications_enabled', ParamType::BOOLEAN, 'Enable/disable all notifications'))->optional(),
                    (new Param('do_not_disturb', ParamType::BOOLEAN, 'Enable/disable do not disturb mode'))->optional(),
                    (new Param('quiet_hours_start', ParamType::STRING, 'Quiet hours start time (HH:MM)'))->optional(),
                    (new Param('quiet_hours_end', ParamType::STRING, 'Quiet hours end time (HH:MM)'))->optional(),
                    (new Param('quiet_hours_timezone', ParamType::STRING, 'Timezone for quiet hours'))->optional(),
                    (new Param('allow_critical_during_quiet', ParamType::BOOLEAN, 'Allow critical notifications during quiet hours'))->optional(),
                    (new Param('category_preferences', ParamType::OBJECT, 'Category-specific preferences'))->optional(),
                    (new Param('sounds_enabled', ParamType::BOOLEAN, 'Enable/disable notification sounds'))->optional(),
                    (new Param('vibration_enabled', ParamType::BOOLEAN, 'Enable/disable vibration'))->optional(),
                    (new Param('badge_enabled', ParamType::BOOLEAN, 'Enable/disable badge count'))->optional(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'notifications_enabled' => 'sometimes|boolean',
            'do_not_disturb' => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end' => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_timezone' => 'sometimes|string|timezone',
            'allow_critical_during_quiet' => 'sometimes|boolean',
            'category_preferences' => 'sometimes|array',
            'category_preferences.*' => 'boolean',
            'sounds_enabled' => 'sometimes|boolean',
            'vibration_enabled' => 'sometimes|boolean',
            'badge_enabled' => 'sometimes|boolean',
        ]);

        $preferences = NotificationPreference::forUser($request->user());

        // Update basic settings
        $fillable = [
            'notifications_enabled',
            'do_not_disturb',
            'allow_critical_during_quiet',
            'sounds_enabled',
            'vibration_enabled',
            'badge_enabled',
        ];

        foreach ($fillable as $field) {
            if ($request->has($field)) {
                $preferences->{$field} = $request->input($field);
            }
        }

        // Update quiet hours
        if ($request->has('quiet_hours_start') || $request->has('quiet_hours_end')) {
            $preferences->setQuietHours(
                $request->input('quiet_hours_start'),
                $request->input('quiet_hours_end'),
                $request->input('quiet_hours_timezone', $preferences->quiet_hours_timezone)
            );
        }

        // Update category preferences
        if ($request->has('category_preferences')) {
            $currentPrefs = $preferences->category_preferences ?? NotificationCategory::defaultPreferences();
            $newPrefs = $request->input('category_preferences');

            foreach ($newPrefs as $category => $enabled) {
                // Don't allow disabling critical categories
                if (!$enabled && in_array($category, NotificationCategory::critical())) {
                    continue;
                }
                $currentPrefs[$category] = $enabled;
            }

            $preferences->category_preferences = $currentPrefs;
        }

        $preferences->save();

        return response()->apiSuccess([
            'preferences' => $preferences->fresh()->toApiArray(),
        ], 'Preferences updated successfully.');
    }

    /**
     * Subscribe to a topic.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribe(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Subscribe to Topic')
                ->setDescription('Subscribe to a notification topic')
                ->setParams([
                    (new Param('topic', ParamType::STRING, 'The topic name to subscribe to'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'topic' => 'required|string',
        ]);

        $topic = $request->input('topic');

        if (!PushNotificationTopic::isValid($topic)) {
            return response()->apiError('Invalid topic.', 422);
        }

        $token = $request->user()->currentAccessToken();

        if (!$token->device_push_token) {
            return response()->apiError('Device push token not set. Please update your push token first.', 422);
        }

        $success = $this->manager->subscribeToTopic($token, $topic);

        if ($success) {
            return response()->apiSuccess([
                'subscriptions' => $token->fresh()->topic_subscriptions,
            ], "Successfully subscribed to {$topic}.");
        }

        return response()->apiError('Failed to subscribe to topic.', 500);
    }

    /**
     * Unsubscribe from a topic.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Unsubscribe from Topic')
                ->setDescription('Unsubscribe from a notification topic')
                ->setParams([
                    (new Param('topic', ParamType::STRING, 'The topic name to unsubscribe from'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'topic' => 'required|string',
        ]);

        $topic = $request->input('topic');
        $token = $request->user()->currentAccessToken();

        $success = $this->manager->unsubscribeFromTopic($token, $topic);

        if ($success) {
            return response()->apiSuccess([
                'subscriptions' => $token->fresh()->topic_subscriptions,
            ], "Successfully unsubscribed from {$topic}.");
        }

        return response()->apiError('Failed to unsubscribe from topic.', 500);
    }

    /**
     * Get current topic subscriptions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function subscriptions(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Subscriptions')
                ->setDescription('Get current topic subscriptions')
                ->setSuccessMessageOnly();
        });

        $token = $request->user()->currentAccessToken();

        return response()->apiSuccess([
            'subscriptions' => $token->topic_subscriptions ?? [],
            'available_topics' => PushNotificationTopic::all(),
        ]);
    }

    /**
     * Get available topics.
     *
     * @return JsonResponse
     */
    public function topics(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Get Topics')
                ->setDescription('Get available notification topics')
                ->setSuccessMessageOnly();
        });

        return response()->apiSuccess([
            'topics' => PushNotificationTopic::all(),
        ]);
    }

    /**
     * Update push token and subscribe to default topics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Update Push Token')
                ->setDescription('Update push notification token and subscribe to default topics')
                ->setParams([
                    (new Param('push_token', ParamType::STRING, 'The new push notification token'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'push_token' => 'required|string',
        ]);

        $token = $request->user()->currentAccessToken();
        $oldPushToken = $token->device_push_token;

        // Update the push token
        $token->update([
            'device_push_token' => $request->input('push_token'),
        ]);

        // If this is a new push token, subscribe to default topics
        if (!$oldPushToken || $oldPushToken !== $request->input('push_token')) {
            $this->manager->subscribeToDefaultTopics($token);
        }

        return response()->apiSuccess([
            'subscriptions' => $token->fresh()->topic_subscriptions,
        ], 'Push token updated successfully.');
    }

    /**
     * Get unread notification count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Notifications')
                ->setName('Unread Count')
                ->setDescription('Get unread notification count')
                ->setSuccessMessageOnly();
        });

        $count = $this->repository->getUnreadCountForUser($request->user());

        return response()->apiSuccess([
            'unread_count' => $count,
        ]);
    }
}
