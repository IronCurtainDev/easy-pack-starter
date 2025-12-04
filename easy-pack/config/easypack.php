<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Easy Pack Starter Kit Configuration
    |--------------------------------------------------------------------------
    |
    | Main configuration file for the Easy Pack Laravel Starter Kit.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Key Authentication
    |--------------------------------------------------------------------------
    |
    | Static API KEY for the application. Supports one or more API keys
    | separated by commas. Use the ApiAuthenticate middleware to protect
    | routes that require API key validation via the x-api-key header.
    |
    */

    'api_key' => env('API_KEY', false),

    /*
    |--------------------------------------------------------------------------
    | Route Loading
    |--------------------------------------------------------------------------
    |
    | Control which routes are automatically loaded by the package.
    |
    | RECOMMENDED: Set load_api_routes to false and use the published
    | routes/api.php file for explicit route definitions. This provides:
    | - Full visibility of all API routes in your application
    | - Consistent versioning (api/v1, api/v2, etc.)
    | - Easier customization and route overrides
    | - Better IDE support and route discovery
    |
    | To publish routes: php artisan vendor:publish --tag=easypack-routes
    |
    */

    'load_web_routes' => env('EASYPACK_LOAD_WEB_ROUTES', true),
    'load_api_routes' => env('EASYPACK_LOAD_API_ROUTES', false),
    'load_admin_routes' => env('EASYPACK_LOAD_ADMIN_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes
    |--------------------------------------------------------------------------
    |
    | Customize the route prefixes for API and admin routes.
    | API routes: /api/v1/auth/login, /api/v1/profile, etc.
    | Admin routes: /manage/users, /manage/roles, etc.
    |
    | Examples:
    |   'api_prefix' => 'api/v1'     -> /api/v1/auth/login
    |   'api_prefix' => 'api/v2'     -> /api/v2/auth/login
    |   'api_prefix' => 'api'        -> /api/auth/login (no versioning)
    |
    */

    'api_prefix' => env('EASYPACK_API_PREFIX', 'api/v1'),
    'admin_prefix' => env('EASYPACK_ADMIN_PREFIX', 'manage'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current API version. Used for controller namespaces and folder
    | structure. Supports enterprise-grade versioning where controllers
    | are organized as Api/V1, Api/V2, etc.
    |
    | Note: This only affects the controller folder structure, not routes.
    | The route prefix is controlled by 'api_prefix' above.
    |
    */

    'api_version' => env('EASYPACK_API_VERSION', 'V1'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    |
    | Enable or disable the admin panel. When disabled, admin routes,
    | controllers, and views will not be loaded.
    |
    */

    'admin_panel_enabled' => env('EASYPACK_ADMIN_PANEL', true),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class to use. Override if you extend the base User model.
    |
    */

    'user_model' => \EasyPack\Starter\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    |
    | Configure personal access token behavior.
    |
    */

    'tokens' => [
        'expiry_days' => env('EASYPACK_TOKEN_EXPIRY_DAYS', 90),
        'single_device_per_type' => env('EASYPACK_SINGLE_DEVICE_PER_TYPE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    |
    | Configure media library settings.
    |
    */

    'media' => [
        'max_file_size' => env('EASYPACK_MAX_FILE_SIZE', 10240), // KB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific modules/features of the package.
    | This allows you to use only the parts you need.
    |
    */

    'modules' => [
        'push_notifications' => env('EASYPACK_MODULE_PUSH_NOTIFICATIONS', true),
        'media_management' => env('EASYPACK_MODULE_MEDIA', true),
        'device_management' => env('EASYPACK_MODULE_DEVICES', true),
        'settings_management' => env('EASYPACK_MODULE_SETTINGS', true),
        'invitations' => env('EASYPACK_MODULE_INVITATIONS', true),
        'api_documentation' => env('EASYPACK_MODULE_API_DOCS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Controllers
    |--------------------------------------------------------------------------
    |
    | When enabled, routes will use controllers from your app instead of
    | the package controllers. This allows full customization of controller
    | logic while still using the package routes structure.
    |
    | To enable:
    | 1. Run: php artisan easypack:publish --customizable
    | 2. Set EASYPACK_USE_LOCAL_CONTROLLERS=true in .env
    |
    | Or selectively enable for API/Admin:
    | - EASYPACK_USE_LOCAL_API_CONTROLLERS=true
    | - EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true
    |
    */

    'use_local_controllers' => env('EASYPACK_USE_LOCAL_CONTROLLERS', false),
    'use_local_api_controllers' => env('EASYPACK_USE_LOCAL_API_CONTROLLERS', false),
    'use_local_admin_controllers' => env('EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS', false),

    /*
    |--------------------------------------------------------------------------
    | Controller Namespaces
    |--------------------------------------------------------------------------
    |
    | Define the namespaces where local controllers should be found.
    | These are used when use_local_controllers is enabled.
    |
    */

    'controller_namespaces' => [
        'api' => 'App\\Http\\Controllers\\Api\\V1',
        'admin' => 'App\\Http\\Controllers\\Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Controller Mappings
    |--------------------------------------------------------------------------
    |
    | Map package controllers to local controllers. When use_local_controllers
    | is enabled, these mappings define which local controller replaces each
    | package controller. Only override controllers you want to customize.
    |
    | Example: Uncomment to use local AuthController instead of package's
    |
    */

    'local_api_controllers' => [
        // 'auth' => \App\Http\Controllers\Api\V1\AuthController::class,
        // 'profile' => \App\Http\Controllers\Api\V1\ProfileController::class,
        // 'guest' => \App\Http\Controllers\Api\V1\GuestController::class,
        // 'device' => \App\Http\Controllers\Api\V1\DeviceController::class,
        // 'media' => \App\Http\Controllers\Api\V1\MediaController::class,
        // 'push_notifications' => \App\Http\Controllers\Api\V1\PushNotificationsController::class,
        // 'settings' => \App\Http\Controllers\Api\V1\SettingsController::class,
        // 'forgot_password' => \App\Http\Controllers\Api\V1\ForgotPasswordController::class,
    ],

    'local_admin_controllers' => [
        // 'dashboard' => \App\Http\Controllers\Admin\DashboardController::class,
        // 'users' => \App\Http\Controllers\Admin\ManageUsersController::class,
        // 'roles' => \App\Http\Controllers\Admin\ManageRolesController::class,
        // 'permissions' => \App\Http\Controllers\Admin\ManagePermissionsController::class,
        // 'invitations' => \App\Http\Controllers\Admin\ManageInvitationsController::class,
    ],

];
