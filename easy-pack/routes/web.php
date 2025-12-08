<?php

use Illuminate\Support\Facades\Route;
use EasyPack\Http\Controllers\Common\PagesController;

/*
|--------------------------------------------------------------------------
| Oxygen Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the OxygenServiceProvider when admin panel
| is enabled. They provide web-based authentication and admin dashboard.
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes (Contact Us)
|--------------------------------------------------------------------------
*/

Route::middleware('web')->group(function () {
    Route::get('/contact-us', [PagesController::class, 'contactUs'])->name('contact-us');
    Route::post('/contact-us', [PagesController::class, 'postContactUs']);
    Route::get('/privacy-policy', [PagesController::class, 'privacyPolicy'])->name('pages.privacy-policy');
    Route::get('/terms-conditions', [PagesController::class, 'termsConditions'])->name('pages.terms-conditions');
});

// Only load if admin panel is enabled
if (!function_exists('is_admin_panel_enabled') || !is_admin_panel_enabled()) {
    return;
}

/*
|--------------------------------------------------------------------------
| Controller Resolution
|--------------------------------------------------------------------------
|
| Resolve controller classes based on config settings.
| When use_local_admin_controllers is true, use local app controllers.
| Otherwise, use the package controllers.
|
*/

$useLocalAdminControllers = config('easypack.use_local_controllers', false)
    || config('easypack.use_local_admin_controllers', false);

$localAdminNamespace = config('easypack.controller_namespaces.admin', 'App\\Http\\Controllers\\Admin');
$localAuthNamespace = 'App\\Http\\Controllers\\Auth';
$packageManageNamespace = 'EasyPack\\Http\\Controllers\\Manage';
$packageAuthNamespace = 'EasyPack\\Http\\Controllers\\Auth';

$getAdminController = function ($name, $className) use ($useLocalAdminControllers, $localAdminNamespace, $packageManageNamespace) {
    // Check for specific controller override in config
    $localMapping = config("oxygen.local_admin_controllers.{$name}");
    if ($localMapping && class_exists($localMapping)) {
        return $localMapping;
    }

    // Use local namespace if enabled and class exists
    if ($useLocalAdminControllers) {
        $localClass = "{$localAdminNamespace}\\{$className}";
        if (class_exists($localClass)) {
            return $localClass;
        }
    }

    // Fall back to package controller
    return "{$packageManageNamespace}\\{$className}";
};

$getAuthController = function ($name, $className) use ($localAuthNamespace, $packageAuthNamespace) {
    // Check for local Auth controller
    $localClass = "{$localAuthNamespace}\\{$className}";
    if (class_exists($localClass)) {
        return $localClass;
    }

    // Fall back to package controller
    return "{$packageAuthNamespace}\\{$className}";
};

// Resolve Admin controllers
$DashboardController = $getAdminController('dashboard', 'DashboardController');
$UsersController = $getAdminController('users', 'UsersController');
$RolesController = $getAdminController('roles', 'RolesController');
$PermissionsController = $getAdminController('permissions', 'PermissionsController');
$DevicesController = $getAdminController('devices', 'DevicesController');
$PushNotificationsController = $getAdminController('push_notifications', 'PushNotificationsController');
$ManageDocumentationController = $getAdminController('documentation', 'ManageDocumentationController');

// Resolve Auth controllers
$LoginController = $getAuthController('login', 'LoginController');
$ProfileController = $getAuthController('profile', 'ProfileController');
$InvitationsController = $getAuthController('invitations', 'InvitationsController');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'guest'])->group(function () use ($LoginController) {
    Route::get('/login', [$LoginController, 'showLoginForm'])->name('login');
    Route::post('/login', [$LoginController, 'login']);
});

Route::post('/logout', [$LoginController, 'logout'])->name('logout')->middleware(['web', 'auth']);

/*
|--------------------------------------------------------------------------
| Invitation Routes (Public)
|--------------------------------------------------------------------------
*/
if (function_exists('has_module') && has_module('invitations')) {
    Route::middleware('web')->group(function () use ($InvitationsController) {
        Route::get('/invitations/join/{code}', [$InvitationsController, 'showJoinForm'])->name('invitations.join');
        Route::post('/invitations/join/{code}', [$InvitationsController, 'join']);
    });
}

/*
|--------------------------------------------------------------------------
| Admin Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth'])->group(function () use (
    $DashboardController,
    $ProfileController,
    $UsersController,
    $RolesController,
    $PermissionsController,
    $InvitationsController,
    $PushNotificationsController,
    $DevicesController,
    $ManageDocumentationController
) {
    // Dashboard - restricted to admin and super-admin roles only
    Route::get('/dashboard', [$DashboardController, 'dashboard'])->name('dashboard')->middleware('role:admin|super-admin');

    // Account/Profile routes
    Route::prefix('account')->name('account.')->group(function () use ($ProfileController) {
        Route::get('/profile', [$ProfileController, 'show'])->name('profile');
        Route::get('/profile/edit', [$ProfileController, 'edit'])->name('profile.edit');
        Route::put('/profile', [$ProfileController, 'update'])->name('profile.update');
        Route::get('/password', [$ProfileController, 'editPassword'])->name('password');
        Route::put('/password', [$ProfileController, 'updatePassword'])->name('password.update');
    });

    // Manage routes - restricted to admin and super-admin roles only
    Route::prefix('manage')->name('manage.')->middleware('role:admin|super-admin')->group(function () use (
        $UsersController,
        $RolesController,
        $PermissionsController,
        $InvitationsController,
        $PushNotificationsController,
        $DevicesController,
        $ManageDocumentationController
    ) {
        // Page Content Management
        $PageContentsController = 'EasyPack\\Http\\Controllers\\Manage\\PageContentsController';
        Route::get('/pages', [$PageContentsController, 'index'])->name('pages.index');
        Route::get('/pages/create', [$PageContentsController, 'create'])->name('pages.create');
        Route::post('/pages', [$PageContentsController, 'store'])->name('pages.store');
        Route::get('/pages/{slug}/edit', [$PageContentsController, 'edit'])->name('pages.edit');
        Route::put('/pages/{slug}', [$PageContentsController, 'update'])->name('pages.update');
        Route::delete('/pages/{slug}', [$PageContentsController, 'destroy'])->name('pages.destroy');

        // Documentation (if enabled)
        if (function_exists('has_module') && has_module('api_documentation')) {
            Route::get('/docs/api', [$ManageDocumentationController, 'index'])->name('documentation.index');
        }

        // User Management
        Route::resource('users', $UsersController);
        Route::get('/users/{user}/edit-password', [$UsersController, 'editPassword'])->name('users.edit-password');
        Route::put('/users/{user}/edit-password', [$UsersController, 'updatePassword'])->name('users.update-password');
        Route::patch('/users/{user}/toggle-disabled', [$UsersController, 'toggleDisabled'])->name('users.toggle-disabled');
        Route::delete('/users/{user}/revoke-tokens', [$UsersController, 'revokeTokens'])->name('users.revoke-tokens');

        // Role Management
        Route::resource('roles', $RolesController)->except(['show']);
        Route::get('/roles/{role}/users', [$RolesController, 'showUsers'])->name('roles.users');
        Route::delete('/roles/{role}/users/{user}', [$RolesController, 'removeUser'])->name('roles.remove-user');

        // Permission Management
        Route::resource('permissions', $PermissionsController)->except(['show']);
        Route::post('/permissions/bulk-create', [$PermissionsController, 'bulkCreate'])->name('permissions.bulk-create');

        // Invitation Management (if enabled)
        if (function_exists('has_module') && has_module('invitations')) {
            Route::resource('invitations', $InvitationsController)->except(['edit', 'update']);
            Route::post('/invitations/{invitation}/resend', [$InvitationsController, 'resend'])->name('invitations.resend');
            Route::delete('/invitations/prune', [$InvitationsController, 'pruneExpired'])->name('invitations.prune');
        }

        // Push Notification Management (if enabled)
        if (function_exists('has_module') && has_module('push_notifications')) {
            Route::resource('push-notifications', $PushNotificationsController)->except(['edit', 'update']);
            Route::post('/push-notifications/{push_notification}/resend', [$PushNotificationsController, 'resend'])->name('push-notifications.resend');
        }

        // Device Management (if enabled)
        if (function_exists('has_module') && has_module('device_management')) {
            Route::get('/devices', [$DevicesController, 'index'])->name('devices.index');
            Route::get('/devices/{device}', [$DevicesController, 'show'])->name('devices.show');
            Route::delete('/devices/{device}', [$DevicesController, 'destroy'])->name('devices.destroy');
            Route::delete('/devices/prune-expired', [$DevicesController, 'pruneExpired'])->name('devices.prune-expired');
            Route::delete('/devices/prune-docs-tokens', [$DevicesController, 'pruneDocsTokens'])->name('devices.prune-docs-tokens');
            Route::delete('/devices/user/{user}', [$DevicesController, 'revokeAllForUser'])->name('devices.revoke-all-for-user');
        }
    });
});
