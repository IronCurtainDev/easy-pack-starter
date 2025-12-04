<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Oxygen API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the OxygenServiceProvider.
| The prefix is configurable via config('easypack.api_prefix').
|
| Default: /api/v1/auth/login, /api/v1/profile, etc.
| Custom:  Set EASYPACK_API_PREFIX=api/v2 in .env for /api/v2/...
|
*/

// Get the API prefix from config (default: 'api/v1')
$apiPrefix = config('easypack.api_prefix', 'api/v1');

/*
|--------------------------------------------------------------------------
| Controller Resolution
|--------------------------------------------------------------------------
|
| Resolve controller classes based on config settings.
| When use_local_api_controllers is true, use local app controllers.
| Otherwise, use the package controllers.
|
*/

$useLocalControllers = config('easypack.use_local_controllers', false)
    || config('easypack.use_local_api_controllers', false);

$localNamespace = config('easypack.controller_namespaces.api', 'App\\Http\\Controllers\\Api\\V1');
$packageNamespace = 'EasyPack\\Http\\Controllers\\Api\\V1';

$getController = function ($name, $className) use ($useLocalControllers, $localNamespace, $packageNamespace) {
    // Check for specific controller override in config
    $localMapping = config("oxygen.local_api_controllers.{$name}");
    if ($localMapping && class_exists($localMapping)) {
        return $localMapping;
    }

    // Use local namespace if enabled and class exists
    if ($useLocalControllers) {
        $localClass = "{$localNamespace}\\{$className}";
        if (class_exists($localClass)) {
            return $localClass;
        }
    }

    // Fall back to package controller
    return "{$packageNamespace}\\{$className}";
};

// Resolve all controllers
$AuthController = $getController('auth', 'AuthController');
$DeviceController = $getController('device', 'DeviceController');
$ForgotPasswordController = $getController('forgot_password', 'ForgotPasswordController');
$GuestController = $getController('guest', 'GuestController');
$MediaController = $getController('media', 'MediaController');
$ProfileController = $getController('profile', 'ProfileController');
$PushNotificationsController = $getController('push_notifications', 'PushNotificationsController');
$SettingsController = $getController('settings', 'SettingsController');

// Only load routes if API is active
if (!has_feature('api.active', true)) {
    return;
}

Route::prefix($apiPrefix)->group(function () use (
    $AuthController,
    $DeviceController,
    $ForgotPasswordController,
    $GuestController,
    $MediaController,
    $ProfileController,
    $PushNotificationsController,
    $SettingsController
) {

    // ==========================================
    // PUBLIC ROUTES (No authentication required)
    // ==========================================

    // Guest routes - public app settings
    Route::get('guests', [$GuestController, 'index']);

    // Auth routes - public
    Route::prefix('auth')->group(function () use ($AuthController) {
        Route::post('login', [$AuthController, 'login']);

        // Registration (only if enabled)
        if (has_feature('auth.public_users_can_register')) {
            Route::post('register', [$AuthController, 'register']);
        }
    });

    // Password reset - public
    Route::prefix('password')->group(function () use ($ForgotPasswordController) {
        Route::post('email', [$ForgotPasswordController, 'checkRequest']);
    });

    // ==========================================
    // AUTHENTICATED ROUTES
    // ==========================================
    Route::middleware(['auth:sanctum'])->group(function () use (
        $AuthController,
        $DeviceController,
        $MediaController,
        $ProfileController,
        $PushNotificationsController,
        $SettingsController
    ) {

        // Auth routes
        Route::prefix('auth')->group(function () use ($AuthController) {
            Route::post('logout', [$AuthController, 'logout']);
            Route::post('logout-all', [$AuthController, 'logoutAll']);
            Route::get('me', [$AuthController, 'me']);
            Route::put('push-token', [$AuthController, 'updatePushToken']);
            Route::post('refresh', [$AuthController, 'refresh']);
        });

        // Email verification routes
        Route::prefix('verify-email')->group(function () use ($AuthController) {
            Route::post('verify', [$AuthController, 'verifyEmail']);
        });
        Route::get('resend-code', [$AuthController, 'resendCode']);

        // Profile routes
        Route::prefix('profile')->group(function () use ($ProfileController) {
            Route::get('/', [$ProfileController, 'show']);
            Route::put('/', [$ProfileController, 'update']);
            Route::put('password', [$ProfileController, 'updatePassword']);
            Route::post('avatar', [$ProfileController, 'updateAvatar']);
            Route::delete('avatar', [$ProfileController, 'deleteAvatar']);

            // Notification preferences (if push notifications enabled)
            if (config('easypack.modules.push_notifications', true)) {
                Route::get('notification-preferences', [$ProfileController, 'notificationPreferences']);
                Route::put('notification-preferences', [$ProfileController, 'updateNotificationPreferences']);
            }

            // Account deletion (if enabled)
            if (has_feature('auth.allow_account_deletion', true)) {
                Route::delete('/', [$ProfileController, 'destroy']);
            }
        });

        // Device management routes (if enabled)
        if (config('easypack.modules.device_management', true)) {
            Route::prefix('devices')->group(function () use ($DeviceController) {
                Route::get('/', [$DeviceController, 'index']);
                Route::get('{device}', [$DeviceController, 'show']);
                Route::delete('{device}', [$DeviceController, 'destroy']);
                Route::put('push-token', [$DeviceController, 'updatePushToken']);
                Route::put('{device}/push-token', [$DeviceController, 'updateDevicePushToken']);
                Route::post('logout-others', [$DeviceController, 'logoutOthers']);
            });
        }

        // Media management routes (if enabled)
        if (config('easypack.modules.media_library', true)) {
            Route::prefix('media')->group(function () use ($MediaController) {
                Route::get('/', [$MediaController, 'index']);
                Route::get('file-keys', [$MediaController, 'getFileKeys']);
                Route::get('by-key/{file_key}', [$MediaController, 'getByFileKey']);
                Route::get('{media}', [$MediaController, 'show']);
                Route::put('{media}', [$MediaController, 'update']);
                Route::delete('{media}', [$MediaController, 'destroy']);
                Route::get('{media}/view', [$MediaController, 'view']);
                Route::get('{media}/download', [$MediaController, 'download']);
            });
        }

        // Push notifications routes (if enabled)
        if (config('easypack.modules.push_notifications', true)) {
            Route::prefix('notifications')->group(function () use ($PushNotificationsController) {
                // Notification management
                Route::get('/', [$PushNotificationsController, 'index']);
                Route::get('device', [$PushNotificationsController, 'forDevice']);
                Route::get('unread-count', [$PushNotificationsController, 'unreadCount']);
                Route::get('categories', [$PushNotificationsController, 'categories']);
                Route::get('priorities', [$PushNotificationsController, 'priorities']);
                Route::get('{notification}', [$PushNotificationsController, 'show']);
                Route::delete('{notification}', [$PushNotificationsController, 'destroy']);
                Route::put('{notification}/read', [$PushNotificationsController, 'markAsRead']);
                Route::put('read-all', [$PushNotificationsController, 'markAllAsRead']);

                // Preferences
                Route::get('preferences', [$PushNotificationsController, 'getPreferences']);
                Route::put('preferences', [$PushNotificationsController, 'updatePreferences']);

                // Topic subscriptions
                Route::prefix('topics')->group(function () use ($PushNotificationsController) {
                    Route::get('/', [$PushNotificationsController, 'topics']);
                    Route::get('subscriptions', [$PushNotificationsController, 'subscriptions']);
                    Route::post('{topic}/subscribe', [$PushNotificationsController, 'subscribe']);
                    Route::delete('{topic}/unsubscribe', [$PushNotificationsController, 'unsubscribe']);
                });

                // Push token management
                Route::put('push-token', [$PushNotificationsController, 'updatePushToken']);
            });
        }

        // Settings routes (if module enabled)
        if (config('easypack.modules.settings_management', true)) {
            // Public read
            Route::get('settings', [$SettingsController, 'index']);
            Route::get('settings/groups', [$SettingsController, 'groups']);
            Route::get('settings/{key}', [$SettingsController, 'show']);

            // Admin write
            Route::middleware(['can:manage-settings'])->group(function () use ($SettingsController) {
                Route::put('settings/{key}', [$SettingsController, 'update']);
            });
        }
    });
});
