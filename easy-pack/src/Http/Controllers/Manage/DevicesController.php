<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\User;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    /**
     * Display a listing of all devices.
     */
    public function index(Request $request)
    {
        $query = PersonalAccessToken::with('tokenable');

        // Filter by user
        if ($userId = $request->get('user_id')) {
            $query->where('tokenable_id', $userId);
        }

        // Filter by device type
        if ($deviceType = $request->get('device_type')) {
            $query->where('device_type', $deviceType);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
            }
        }

        // Search by device ID or name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('device_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $devices = $query->orderBy('last_used_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => PersonalAccessToken::count(),
            'active' => PersonalAccessToken::active()->count(),
            'with_push_token' => PersonalAccessToken::whereNotNull('device_push_token')->count(),
            'apple' => PersonalAccessToken::where('device_type', 'apple')->count(),
            'android' => PersonalAccessToken::where('device_type', 'android')->count(),
            'docs_generation' => PersonalAccessToken::where('name', 'docs-generation')->count(),
        ];

        // Get users for filter
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('easypack::manage.devices.index', compact('devices', 'stats', 'users'));
    }

    /**
     * Display the specified device.
     */
    public function show(PersonalAccessToken $device)
    {
        $device->load('tokenable');

        return view('easypack::manage.devices.show', compact('device'));
    }

    /**
     * Revoke a device token.
     */
    public function destroy(PersonalAccessToken $device)
    {
        $deviceName = $device->name;
        $userName = $device->tokenable?->name ?? 'Unknown User';
        $device->delete();

        return redirect()->route('manage.devices.index')
            ->with('success', "Device '{$deviceName}' for user '{$userName}' has been revoked.");
    }

    /**
     * Revoke all devices for a user.
     */
    public function revokeAllForUser(User $user)
    {
        $count = $user->tokens()->delete();

        return redirect()->route('manage.devices.index')
            ->with('success', "Revoked {$count} device(s) for user '{$user->name}'.");
    }

    /**
     * Revoke all expired tokens.
     */
    public function pruneExpired()
    {
        $count = PersonalAccessToken::where('expires_at', '<=', now())->delete();

        return redirect()->route('manage.devices.index')
            ->with('success', "Pruned {$count} expired token(s).");
    }

    /**
     * Revoke all docs-generation tokens.
     */
    public function pruneDocsTokens()
    {
        $count = PersonalAccessToken::where('name', 'docs-generation')->delete();

        return redirect()->route('manage.devices.index')
            ->with('success', "Pruned {$count} docs-generation token(s).");
    }

    /**
     * Update push token for a device.
     */
    public function updatePushToken(Request $request, PersonalAccessToken $device)
    {
        $validated = $request->validate([
            'device_push_token' => 'nullable|string|max:500',
        ]);

        $device->update([
            'device_push_token' => $validated['device_push_token'],
        ]);

        return redirect()->route('manage.devices.show', $device)
            ->with('success', 'Push token updated successfully.');
    }
}
