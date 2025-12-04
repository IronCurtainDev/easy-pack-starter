<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\PushNotification;
use EasyPack\Models\User;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\NotificationBuilder;
use EasyPack\Services\PushNotifications\NotificationCategory;
use EasyPack\Services\PushNotifications\NotificationPriority;
use Illuminate\Http\Request;

class PushNotificationsController extends Controller
{
    /**
     * Display a listing of push notifications.
     */
    public function index(Request $request)
    {
        $query = PushNotification::with('notifiable');

        // Filter by status
        if ($status = $request->get('status')) {
            if ($status === 'sent') {
                $query->whereNotNull('sent_at');
            } elseif ($status === 'pending') {
                $query->whereNull('sent_at');
            }
        }

        // Filter by category
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => PushNotification::count(),
            'sent' => PushNotification::whereNotNull('sent_at')->count(),
            'pending' => PushNotification::whereNull('sent_at')->count(),
        ];

        // Get categories for filter
        $categories = PushNotification::distinct()->pluck('category')->filter();

        return view('easypack::manage.push-notifications.index', compact('notifications', 'stats', 'categories'));
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $topics = config('push-notifications.topics', []);
        $categories = config('push-notifications.categories', []);
        $priorities = ['normal', 'high'];

        return view('easypack::manage.push-notifications.create', compact('users', 'topics', 'categories', 'priorities'));
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'send_type' => 'required|in:user,topic,all',
            'user_id' => 'required_if:send_type,user|nullable|exists:users,id',
            'topic' => 'required_if:send_type,topic|nullable|string',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|in:normal,high',
            'data' => 'nullable|array',
            'image_url' => 'nullable|url',
            'action_url' => 'nullable|string',
        ]);

        $sendType = $validated['send_type'];

        if ($sendType === 'user') {
            // Send to specific user
            $user = User::find($validated['user_id']);
            $builder = NotificationBuilder::create()
                ->title($validated['title'])
                ->message($validated['body'])
                ->data($validated['data'] ?? [])
                ->toUser($user);

            // Set priority if specified
            if (($validated['priority'] ?? 'normal') === 'high') {
                $builder->highPriority();
            }

            $notification = $builder->save();

            return redirect()->route('manage.push-notifications.index')
                ->with('success', "Notification queued for user '{$user->name}'.");
        } elseif ($sendType === 'topic') {
            // Send to topic
            $builder = NotificationBuilder::create()
                ->title($validated['title'])
                ->message($validated['body'])
                ->data($validated['data'] ?? [])
                ->toTopic($validated['topic']);

            // Set priority if specified
            if (($validated['priority'] ?? 'normal') === 'high') {
                $builder->highPriority();
            }

            $notification = $builder->save();

            return redirect()->route('manage.push-notifications.index')
                ->with('success', "Notification queued for topic '{$validated['topic']}'.");
        } else {
            // Send to all users with push tokens
            $count = 0;
            $tokens = PersonalAccessToken::whereNotNull('device_push_token')
                ->with('tokenable')
                ->get();

            foreach ($tokens->groupBy('tokenable_id') as $userId => $userTokens) {
                $user = $userTokens->first()->tokenable;
                if ($user) {
                    $builder = NotificationBuilder::create()
                        ->title($validated['title'])
                        ->message($validated['body'])
                        ->data($validated['data'] ?? [])
                        ->toUser($user);

                    // Set priority if specified
                    if (($validated['priority'] ?? 'normal') === 'high') {
                        $builder->highPriority();
                    }

                    $builder->save();
                    $count++;
                }
            }

            return redirect()->route('manage.push-notifications.index')
                ->with('success', "Notification queued for {$count} user(s).");
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(PushNotification $pushNotification)
    {
        $pushNotification->load(['notifiable', 'status']);

        return view('easypack::manage.push-notifications.show', compact('pushNotification'));
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(PushNotification $pushNotification)
    {
        $pushNotification->delete();

        return redirect()->route('manage.push-notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Resend a notification (reset sent_at to queue it again).
     */
    public function resend(PushNotification $pushNotification)
    {
        $pushNotification->update([
            'sent_at' => null,
            'scheduled_at' => now(),
        ]);

        return redirect()->route('manage.push-notifications.index')
            ->with('success', 'Notification has been requeued for sending.');
    }
}
