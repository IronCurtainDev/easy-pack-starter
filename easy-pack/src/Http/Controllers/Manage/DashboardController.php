<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\PushNotification;
use EasyPack\Models\User;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $data = [
            'appName' => config('app.name'),
            'pageTitle' => config('app.name') . ' Dashboard',
        ];

        $metrics = new Collection();

        // Users metric
        $metrics->push([
            'title' => 'Total Users',
            'count' => User::count(),
            'description' => 'Registered users',
            'route' => 'manage.users.index',
            'icon' => 'fas fa-users',
            'color' => 'primary',
        ]);

        // Active Devices/Sessions metric
        $metrics->push([
            'title' => 'Active Devices',
            'count' => PersonalAccessToken::active()->count(),
            'description' => 'Currently active sessions',
            'route' => null,
            'icon' => 'fas fa-mobile-alt',
            'color' => 'success',
        ]);

        // Push Notifications metric
        $notificationsSent = PushNotification::sent()->count();
        $metrics->push([
            'title' => 'Notifications Sent',
            'count' => $notificationsSent,
            'description' => 'Total push notifications',
            'route' => null,
            'icon' => 'fas fa-bell',
            'color' => 'info',
        ]);

        // Media Files metric
        try {
            $mediaCount = Media::count();
        } catch (\Exception $e) {
            $mediaCount = 0;
        }
        $metrics->push([
            'title' => 'Media Files',
            'count' => $mediaCount,
            'description' => 'Uploaded files',
            'route' => null,
            'icon' => 'fas fa-photo-video',
            'color' => 'warning',
        ]);

        $data['metrics'] = $metrics;

        // Recent activity
        $data['recentUsers'] = User::latest()->take(5)->get();
        $data['recentNotifications'] = PushNotification::sent()->latest('sent_at')->take(5)->get();

        return view('easypack::manage.dashboard.index', $data);
    }
}
