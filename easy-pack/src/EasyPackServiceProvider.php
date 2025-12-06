<?php

namespace EasyPack;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use EasyPack\Console\Commands\InstallCommand;
use EasyPack\Console\Commands\PublishCustomizableCommand;
use EasyPack\Console\Commands\PurgeExpiredTokens;
use EasyPack\Console\Commands\PurgeOldNotifications;
use EasyPack\Console\Commands\SendPushNotifications;
use EasyPack\Console\Commands\Scaffolding\MakeAdminControllerCommand;
use EasyPack\Console\Commands\Scaffolding\MakeAPIControllerCommand;
use EasyPack\Console\Commands\Scaffolding\MakeCrudCommand;
use EasyPack\Console\Commands\Scaffolding\MakeEasyPackModelCommand;
use EasyPack\Console\Commands\Scaffolding\MakeEasyPackRepositoryCommand;
use EasyPack\ApiDocs\Console\Commands\GenerateDocsCommand;
use EasyPack\ApiDocs\Console\Commands\GenerateApiTestsCommand;
use EasyPack\ApiDocs\Console\Commands\GenerateDocsTestsCommand;
use EasyPack\ApiDocs\Docs\DocBuilder;
use EasyPack\Contracts\MediaServiceInterface;
use EasyPack\Contracts\PermissionServiceInterface;
use EasyPack\Contracts\TokenRepositoryInterface;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Services\Navigator;
use EasyPack\Services\SanctumTokenRepository;
use EasyPack\Services\SettingsManager;
use EasyPack\Services\SpatieMediaService;
use EasyPack\Services\SpatiePermissionService;
use EasyPack\Services\UIHelper;

class EasyPackServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config files
        $this->mergeConfigFrom(__DIR__ . '/../config/easypack.php', 'easypack');
        $this->mergeConfigFrom(__DIR__ . '/../config/easypack-features.php', 'features');
        $this->mergeConfigFrom(__DIR__ . '/../config/easypack-push-notifications.php', 'push-notifications');
        $this->mergeConfigFrom(__DIR__ . '/../config/api-docs.php', 'api-docs');

        // Register SettingsManager as singleton
        $this->app->singleton(SettingsManager::class);

        // Register Navigator as singleton
        $this->app->singleton('navigator', function () {
            return new Navigator();
        });

        // Register UIHelper as singleton
        $this->app->singleton(UIHelper::class);

        // Register DocBuilder as singleton for API documentation
        if (!$this->app->environment('production')) {
            $this->app->singleton('api-docs.builder', DocBuilder::class);
        }

        // Register abstraction layer services (for version compatibility)
        $this->registerAbstractionServices();

        // Register all commands
        $this->registerCommands();
    }

    /**
     * Register abstraction layer services.
     * 
     * These allow swapping implementations when upgrading to new
     * Laravel/Sanctum/Spatie versions without breaking changes.
     */
    protected function registerAbstractionServices(): void
    {
        // Token Repository (Sanctum abstraction)
        $this->app->singleton(TokenRepositoryInterface::class, function ($app) {
            return new SanctumTokenRepository();
        });

        // Permission Service (Spatie Permission abstraction)
        $this->app->singleton(PermissionServiceInterface::class, function ($app) {
            return new SpatiePermissionService();
        });

        // Media Service (Spatie Media Library abstraction)
        $this->app->singleton(MediaServiceInterface::class, function ($app) {
            return new SpatieMediaService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use custom PersonalAccessToken model with device fields
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Rate limiting for API
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Super-admin bypass: grant all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Register API response macros
        $this->registerResponseMacros();

        // Register middleware aliases
        $this->registerMiddlewareAliases();

        // Boot publishable assets
        $this->bootPublishables();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutes();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'easypack');

        // Register navigation items for admin panel
        $this->registerNavigationItems();
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        // Register middleware aliases that can be used in routes
        $router->aliasMiddleware('api.key', \EasyPack\Http\Middleware\ApiAuthenticate::class);
        $router->aliasMiddleware('convert.x-access-token', \EasyPack\Http\Middleware\ConvertXAccessToken::class);
        $router->aliasMiddleware('track.device', \EasyPack\Http\Middleware\TrackDeviceActivity::class);
        
        // Register Spatie Permission middleware aliases
        $router->aliasMiddleware('role', \Spatie\Permission\Middleware\RoleMiddleware::class);
        $router->aliasMiddleware('permission', \Spatie\Permission\Middleware\PermissionMiddleware::class);
        $router->aliasMiddleware('role_or_permission', \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        // Always register these commands
        $this->commands([
            InstallCommand::class,
            PublishCustomizableCommand::class,
            PurgeExpiredTokens::class,
            PurgeOldNotifications::class,
            SendPushNotifications::class,
            GenerateDocsCommand::class,
            GenerateApiTestsCommand::class,
            GenerateDocsTestsCommand::class,
        ]);

        // Register scaffolding commands only in local/testing environments
        if ($this->app->environment(['local', 'testing'])) {
            $this->commands([
                MakeEasyPackModelCommand::class,
                MakeEasyPackRepositoryCommand::class,
                MakeAPIControllerCommand::class,
                MakeAdminControllerCommand::class,
                MakeCrudCommand::class,
            ]);
        }
    }

    /**
     * Boot publishable assets.
     */
    protected function bootPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config files
            $this->publishes([
                __DIR__ . '/../config/easypack.php' => config_path('easypack.php'),
                __DIR__ . '/../config/easypack-features.php' => config_path('easypack-features.php'),
                __DIR__ . '/../config/easypack-push-notifications.php' => config_path('easypack-push-notifications.php'),
                __DIR__ . '/../config/api-docs.php' => config_path('api-docs.php'),
            ], 'easypack-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'easypack-migrations');

            // Publish seeders
            $this->publishes([
                __DIR__ . '/../database/seeders' => database_path('seeders'),
                __DIR__ . '/../stubs/UsersSeeder.stub' => database_path('seeders/UsersSeeder.php'),
                __DIR__ . '/../stubs/DatabaseSeeder.stub' => database_path('seeders/DatabaseSeeder.php'),
            ], 'easypack-seeders');

            // Publish stubs for scaffolding
            $this->publishes([
                __DIR__ . '/Console/Commands/Scaffolding/stubs' => base_path('stubs/easypack'),
            ], 'easypack-stubs');

            // Publish .env.example for API key configuration
            $this->publishes([
                __DIR__ . '/../stubs/.env.example' => base_path('.env.example.easypack'),
            ], 'easypack-env-example');

            // Publish routes (recommended approach - explicit route definitions)
            $this->publishes([
                __DIR__ . '/../stubs/routes/api.stub' => base_path('routes/api.php'),
            ], 'easypack-routes');

            // Publish routes as separate file (alternative - for use alongside existing api.php)
            $this->publishes([
                __DIR__ . '/../routes/api.php' => base_path('routes/easypack-api.php'),
            ], 'easypack-routes-separate');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/easypack'),
            ], 'easypack-views');

            // Publish docs assets (swagger.html, apidoc.json)
            $this->publishes([
                __DIR__ . '/../resources/assets/docs/swagger.html' => public_path('docs/swagger.html'),
                __DIR__ . '/../resources/assets/apidoc.json' => base_path('apidoc.json'),
            ], 'easypack-docs');

            // Publish customizable User model
            $this->publishes([
                __DIR__ . '/../stubs/models/User.stub' => app_path('Models/User.php'),
                __DIR__ . '/../stubs/models/PersonalAccessToken.stub' => app_path('Models/PersonalAccessToken.php'),
                __DIR__ . '/../stubs/models/Setting.stub' => app_path('Models/Setting.php'),
                __DIR__ . '/../stubs/models/SettingGroup.stub' => app_path('Models/SettingGroup.php'),
                __DIR__ . '/../stubs/models/Invitation.stub' => app_path('Models/Invitation.php'),
                __DIR__ . '/../stubs/models/PushNotification.stub' => app_path('Models/PushNotification.php'),
                __DIR__ . '/../stubs/models/NotificationPreference.stub' => app_path('Models/NotificationPreference.php'),
            ], 'easypack-models');

            // Publish customizable API controllers
            $this->publishes([
                __DIR__ . '/../stubs/controllers/Api/V1/AuthController.stub' => app_path('Http/Controllers/Api/V1/AuthController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ProfileController.stub' => app_path('Http/Controllers/Api/V1/ProfileController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/GuestController.stub' => app_path('Http/Controllers/Api/V1/GuestController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ForgotPasswordController.stub' => app_path('Http/Controllers/Api/V1/ForgotPasswordController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/DeviceController.stub' => app_path('Http/Controllers/Api/V1/DeviceController.php'),
            ], 'easypack-api-controllers');

            // Publish customizable Admin controllers
            $this->publishes([
                __DIR__ . '/../stubs/controllers/Admin/DashboardController.stub' => app_path('Http/Controllers/Admin/DashboardController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageUsersController.stub' => app_path('Http/Controllers/Admin/ManageUsersController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageRolesController.stub' => app_path('Http/Controllers/Admin/ManageRolesController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManagePermissionsController.stub' => app_path('Http/Controllers/Admin/ManagePermissionsController.php'),
            ], 'easypack-admin-controllers');

            // Publish customizable Web Auth controllers (login, profile for admin panel)
            $this->publishes([
                __DIR__ . '/../stubs/controllers/Auth/LoginController.stub' => app_path('Http/Controllers/Auth/LoginController.php'),
                __DIR__ . '/../stubs/controllers/Auth/ProfileController.stub' => app_path('Http/Controllers/Auth/ProfileController.php'),
            ], 'easypack-auth-controllers');

            // Publish all controllers (API + Admin + Auth)
            $this->publishes([
                __DIR__ . '/../stubs/controllers/Api/V1/AuthController.stub' => app_path('Http/Controllers/Api/V1/AuthController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ProfileController.stub' => app_path('Http/Controllers/Api/V1/ProfileController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/GuestController.stub' => app_path('Http/Controllers/Api/V1/GuestController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ForgotPasswordController.stub' => app_path('Http/Controllers/Api/V1/ForgotPasswordController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/DeviceController.stub' => app_path('Http/Controllers/Api/V1/DeviceController.php'),
                __DIR__ . '/../stubs/controllers/Admin/DashboardController.stub' => app_path('Http/Controllers/Admin/DashboardController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageUsersController.stub' => app_path('Http/Controllers/Admin/ManageUsersController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageRolesController.stub' => app_path('Http/Controllers/Admin/ManageRolesController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManagePermissionsController.stub' => app_path('Http/Controllers/Admin/ManagePermissionsController.php'),
                __DIR__ . '/../stubs/controllers/Auth/LoginController.stub' => app_path('Http/Controllers/Auth/LoginController.php'),
                __DIR__ . '/../stubs/controllers/Auth/ProfileController.stub' => app_path('Http/Controllers/Auth/ProfileController.php'),
            ], 'easypack-controllers');

            // Publish customizable entities (repositories)
            $this->publishes([
                __DIR__ . '/../stubs/entities/BaseRepository.stub' => app_path('Entities/BaseRepository.php'),
                __DIR__ . '/../stubs/entities/Users/UsersRepository.stub' => app_path('Entities/Users/UsersRepository.php'),
                __DIR__ . '/../stubs/entities/Media/Media.stub' => app_path('Entities/Media/Media.php'),
                __DIR__ . '/../stubs/entities/Media/MediaRepository.stub' => app_path('Entities/Media/MediaRepository.php'),
                __DIR__ . '/../stubs/entities/Settings/SettingsRepository.stub' => app_path('Entities/Settings/SettingsRepository.php'),
                __DIR__ . '/../stubs/entities/Settings/SettingGroupsRepository.stub' => app_path('Entities/Settings/SettingGroupsRepository.php'),
            ], 'easypack-entities');

            // Publish all customizable files (models, controllers, entities)
            $this->publishes([
                __DIR__ . '/../stubs/models/User.stub' => app_path('Models/User.php'),
                __DIR__ . '/../stubs/models/PersonalAccessToken.stub' => app_path('Models/PersonalAccessToken.php'),
                __DIR__ . '/../stubs/models/Setting.stub' => app_path('Models/Setting.php'),
                __DIR__ . '/../stubs/models/SettingGroup.stub' => app_path('Models/SettingGroup.php'),
                __DIR__ . '/../stubs/models/Invitation.stub' => app_path('Models/Invitation.php'),
                __DIR__ . '/../stubs/models/PushNotification.stub' => app_path('Models/PushNotification.php'),
                __DIR__ . '/../stubs/models/NotificationPreference.stub' => app_path('Models/NotificationPreference.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/AuthController.stub' => app_path('Http/Controllers/Api/V1/AuthController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ProfileController.stub' => app_path('Http/Controllers/Api/V1/ProfileController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/GuestController.stub' => app_path('Http/Controllers/Api/V1/GuestController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/ForgotPasswordController.stub' => app_path('Http/Controllers/Api/V1/ForgotPasswordController.php'),
                __DIR__ . '/../stubs/controllers/Api/V1/DeviceController.stub' => app_path('Http/Controllers/Api/V1/DeviceController.php'),
                __DIR__ . '/../stubs/controllers/Admin/DashboardController.stub' => app_path('Http/Controllers/Admin/DashboardController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageUsersController.stub' => app_path('Http/Controllers/Admin/ManageUsersController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManageRolesController.stub' => app_path('Http/Controllers/Admin/ManageRolesController.php'),
                __DIR__ . '/../stubs/controllers/Admin/ManagePermissionsController.stub' => app_path('Http/Controllers/Admin/ManagePermissionsController.php'),
                __DIR__ . '/../stubs/controllers/Auth/LoginController.stub' => app_path('Http/Controllers/Auth/LoginController.php'),
                __DIR__ . '/../stubs/controllers/Auth/ProfileController.stub' => app_path('Http/Controllers/Auth/ProfileController.php'),
                __DIR__ . '/../stubs/entities/BaseRepository.stub' => app_path('Entities/BaseRepository.php'),
                __DIR__ . '/../stubs/entities/Users/UsersRepository.stub' => app_path('Entities/Users/UsersRepository.php'),
                __DIR__ . '/../stubs/entities/Media/Media.stub' => app_path('Entities/Media/Media.php'),
                __DIR__ . '/../stubs/entities/Media/MediaRepository.stub' => app_path('Entities/Media/MediaRepository.php'),
                __DIR__ . '/../stubs/entities/Settings/SettingsRepository.stub' => app_path('Entities/Settings/SettingsRepository.php'),
                __DIR__ . '/../stubs/entities/Settings/SettingGroupsRepository.stub' => app_path('Entities/Settings/SettingGroupsRepository.php'),
            ], 'easypack-customizable');

            // Publish all
            $this->publishes([
                __DIR__ . '/../config/easypack.php' => config_path('easypack.php'),
                __DIR__ . '/../config/easypack-features.php' => config_path('easypack-features.php'),
                __DIR__ . '/../config/easypack-push-notifications.php' => config_path('easypack-push-notifications.php'),
                __DIR__ . '/../database/migrations' => database_path('migrations'),
                __DIR__ . '/../database/seeders' => database_path('seeders'),
                __DIR__ . '/../resources/assets/docs/swagger.html' => public_path('docs/swagger.html'),
                __DIR__ . '/../resources/assets/apidoc.json' => base_path('apidoc.json'),
            ], 'easypack');
        }
    }

    /**
     * Load routes.
     */
    protected function loadRoutes(): void
    {
        // Load API routes (if enabled)
        if (config('easypack.load_api_routes', true)) {
            if (file_exists(__DIR__ . '/../routes/api.php')) {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            }
        }

        // Load admin/web routes (if admin panel enabled)
        if (config('easypack.admin_panel_enabled', true) && config('easypack.load_admin_routes', true)) {
            if (file_exists(__DIR__ . '/../routes/admin.php')) {
                $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
            }
        }

        // Load web routes (if enabled)
        if (config('easypack.load_web_routes', true)) {
            if (file_exists(__DIR__ . '/../routes/web.php')) {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            }
        }
    }

    /**
     * Register navigation items for the admin sidebar.
     */
    protected function registerNavigationItems(): void
    {
        // Skip if admin panel is disabled
        if (!config('easypack.admin_panel_enabled', true)) {
            return;
        }

        // Dashboard
        Navigator::addItem([
            'text' => 'Dashboard',
            'resource' => 'dashboard',
            'icon_class' => 'fas fa-tachometer-alt',
            'order' => 1,
        ], 'sidebar');

        // User Management
        Navigator::addItem([
            'text' => 'Users',
            'resource' => 'manage.users.index',
            'icon_class' => 'fas fa-users',
            'order' => 10,
        ], 'sidebar');

        // Pages menu item
        Navigator::addItem([
            'text' => 'Pages',
            'resource' => 'manage.pages.index',
            'icon_class' => 'fas fa-file-alt',
            'order' => 11,
        ], 'sidebar');

        // Role Management
        Navigator::addItem([
            'text' => 'Roles',
            'resource' => 'manage.roles.index',
            'icon_class' => 'fas fa-user-tag',
            'order' => 12,
        ], 'sidebar');

        // Permission Management
        Navigator::addItem([
            'text' => 'Permissions',
            'resource' => 'manage.permissions.index',
            'icon_class' => 'fas fa-key',
            'order' => 13,
        ], 'sidebar');

        // Device Management (if module enabled)
        if (function_exists('has_module') && has_module('device_management')) {
            Navigator::addItem([
                'text' => 'Devices',
                'resource' => 'manage.devices.index',
                'icon_class' => 'fas fa-mobile-alt',
                'order' => 20,
            ], 'sidebar');
        }

        // Invitation Management (if module enabled)
        if (function_exists('has_module') && has_module('invitations')) {
            Navigator::addItem([
                'text' => 'Invitations',
                'resource' => 'manage.invitations.index',
                'icon_class' => 'fas fa-envelope-open-text',
                'order' => 30,
            ], 'sidebar');
        }

        // Push Notifications (if module enabled)
        if (function_exists('has_module') && has_module('push_notifications')) {
            Navigator::addItem([
                'text' => 'Push Notifications',
                'resource' => 'manage.push-notifications.index',
                'icon_class' => 'fas fa-bell',
                'order' => 40,
            ], 'sidebar');
        }

        // API Documentation (if module enabled)
        if (function_exists('has_module') && has_module('api_documentation')) {
            Navigator::addItem([
                'text' => 'API Docs',
                'resource' => 'manage.documentation.index',
                'icon_class' => 'fas fa-book',
                'order' => 50,
            ], 'sidebar');
        }
    }

    /**
     * Register response macros for consistent API responses.
     */
    protected function registerResponseMacros(): void
    {
        /**
         * Return a successful API response.
         */
        Response::macro('apiSuccess', function ($data = null, string $message = '', int $statusCode = 200) {
            return response()->json([
                'result' => true,
                'message' => $message,
                'payload' => $data,
            ], $statusCode);
        });

        /**
         * Return an error API response.
         */
        Response::macro('apiError', function (string $message = 'An error occurred', int $statusCode = 400, $data = null) {
            $response = [
                'result' => false,
                'message' => $message,
            ];

            if ($data !== null) {
                $response['payload'] = $data;
            }

            return response()->json($response, $statusCode);
        });

        /**
         * Return a paginated API response.
         */
        Response::macro('apiSuccessPaginated', function ($paginator, string $message = '') {
            return response()->json([
                'result' => true,
                'message' => $message,
                'payload' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ]);
        });

        /**
         * Return a not found API response.
         */
        Response::macro('apiNotFound', function (string $message = 'Resource not found') {
            return response()->json([
                'result' => false,
                'message' => $message,
            ], 404);
        });

        /**
         * Return an unauthorized API response.
         */
        Response::macro('apiUnauthorized', function (string $message = 'Unauthorized') {
            return response()->json([
                'result' => false,
                'message' => $message,
            ], 401);
        });

        /**
         * Return a validation error API response.
         */
        Response::macro('apiValidationError', function (array|\Illuminate\Support\MessageBag $errors, string $message = 'Validation failed') {
            // Convert MessageBag to array if needed
            if ($errors instanceof \Illuminate\Support\MessageBag) {
                $errors = $errors->toArray();
            }
            
            return response()->json([
                'result' => false,
                'message' => $message,
                'errors' => $errors,
            ], 422);
        });
    }
}
