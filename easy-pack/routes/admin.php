<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Oxygen Admin Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the OxygenServiceProvider when admin panel
| is enabled. The prefix is configurable via config('easypack.admin_prefix').
|
| Default: /manage/dashboard, /manage/users, etc.
|
*/

// Only load if admin panel is enabled
if (!is_admin_panel_enabled()) {
    return;
}

$adminPrefix = oxygen_admin_prefix();

Route::prefix($adminPrefix)->middleware(['web', 'auth'])->group(function () {

    // Dashboard
    // Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

    // Users Management
    // Route::resource('users', UsersController::class)->names('admin.users');

    // Roles Management
    // Route::resource('roles', RolesController::class)->names('admin.roles');

    // Permissions Management
    // Route::resource('permissions', PermissionsController::class)->names('admin.permissions');

    // Device Management (if module enabled)
    if (has_module('device_management')) {
        // Route::resource('devices', DevicesController::class)->only(['index', 'show', 'destroy'])->names('admin.devices');
    }

    // Push Notifications Management (if module enabled)
    if (has_module('push_notifications')) {
        // Route::resource('push-notifications', PushNotificationsController::class)->names('admin.push-notifications');
    }

    // Settings Management (if module enabled)
    if (has_module('settings_management')) {
        // Route::resource('settings', SettingsController::class)->names('admin.settings');
    }

    // Invitations (if module enabled)
    if (has_module('invitations')) {
        // Route::resource('invitations', InvitationsController::class)->names('admin.invitations');
    }
});
