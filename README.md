# Easy Pack

A comprehensive Laravel starter kit for building API-driven applications with mobile app support.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/easypack/starter.svg?style=flat-square)](https://packagist.org/packages/easypack/starter)
[![Total Downloads](https://img.shields.io/packagist/dt/easypack/starter.svg?style=flat-square)](https://packagist.org/packages/easypack/starter)
[![License](https://img.shields.io/packagist/l/easypack/starter.svg?style=flat-square)](https://packagist.org/packages/easypack/starter)

## Quick Start

### Option 1: Create a New Project (Recommended)

```bash
# Create a new Laravel project with Easy Pack
composer create-project easypack/starter my-project

# Navigate to the project directory
cd my-project

# Setup the environment (database, etc.)
# Update .env with your database credentials first!

# Run the installer
php artisan easypack:install

# Generate API documentation and tests
php artisan generate:docs-tests

# Start the server
php artisan serve
```

### Option 2: Add to Existing Project

```bash
# Install the package
composer require easypack/starter

# Run the installer
php artisan easypack:install
```

The installer handles database setup, migrations, seeders, and configuration automatically.

## Features

- 🔐 **Device-based Authentication** - Token management with device tracking, push token support, and automatic session expiry
- 📱 **Push Notifications** - Firebase Cloud Messaging integration with topics, categories, and quiet hours
- 🖼️ **Media Management** - Spatie Media Library integration with convenient traits
- ⚙️ **Settings Management** - Key-value settings with groups and type casting
- 🛡️ **Role & Permission** - Spatie Permission integration with pre-configured roles
- 🧩 **Scaffolding Commands** - Generate models, repositories, controllers, and views
- 📝 **Repository Pattern** - Base repository with search, pagination, and filtering
- 🌐 **API Response Macros** - Consistent JSON response format for APIs

## Requirements

- PHP 8.2+
- Laravel 11.0+
- MySQL 5.7+ / PostgreSQL 9.6+

## Installation

### Option 1: Install as a package (recommended)

```bash
composer require easypack/starter
```

Then run the install command:

```bash
php artisan easypack:install
```

### Option 2: Create new project

```bash
composer create-project easypack/starter my-project
```

### Local Development (Ubuntu & Windows)

For local development before publishing to Packagist:

**Ubuntu/Linux:**
```bash
./install-local.sh my-project --quick
```

**Windows:**
```powershell
.\install-local.ps1 my-project -Quick
```

See [PORTABLE.md](PORTABLE.md) for detailed instructions on portable installations across Ubuntu and Windows.


## Configuration

After installation, publish the config files:

```bash
php artisan vendor:publish --tag=easypack-config
```

This will publish:
- `config/easypack.php` - Main configuration
- `config/features.php` - Feature toggles
- `config/push-notifications.php` - Push notification settings

## Setup

### 1. Publish and Configure API Routes (Required)

Easy Pack requires explicit route definitions in your application. This provides full visibility and control over your API routes.

```bash
# Publish the routes file
php artisan vendor:publish --tag=easypack-routes
```

**For Laravel 11+**, update `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // Add this line
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ...
```

Verify routes are registered:

```bash
php artisan route:list --path=api/v1
```

### 2. Configure Sanctum

In `config/sanctum.php`:

```php
'personal_access_token' => EasyPack\Starter\Models\PersonalAccessToken::class,
```

### 3. Update your User model

```php
use EasyPack\Starter\Models\User as BaseUser;

class User extends BaseUser
{
    // Your customizations
}
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Seed roles and permissions

```bash
php artisan db:seed --class=EasyPack\\Starter\\Database\\Seeders\\EasyPackSeeder
```

## Usage

### Customization

Easy Pack allows you to customize models, controllers, and entities by publishing them to your app. This gives you full control over the implementation while still benefiting from package updates.

#### Quick Start: Publish All Customizable Files

```bash
# Publish everything (all models, API/Admin/Auth controllers, repositories)
php artisan easypack:publish --customizable

# Also enable local controllers in .env
php artisan easypack:publish --customizable --enable
```

This publishes 24 files:
- **7 Models**: User, PersonalAccessToken, Setting, SettingGroup, Invitation, PushNotification, NotificationPreference
- **5 API Controllers**: Auth, Profile, Guest, ForgotPassword, Device
- **4 Admin Controllers**: Dashboard, ManageUsers, ManageRoles, ManagePermissions
- **2 Auth Controllers**: Login, Profile (web auth)
- **6 Entities**: BaseRepository, UsersRepository, Media, MediaRepository, SettingsRepository, SettingGroupsRepository

#### Selective Publishing

```bash
# Publish only all models (User, PersonalAccessToken, Setting, etc.)
php artisan easypack:publish --models

# Publish only API controllers
php artisan easypack:publish --api-controllers

# Publish only Admin controllers
php artisan easypack:publish --admin-controllers

# Publish only Web Auth controllers (Login, Profile)
php artisan easypack:publish --auth-controllers

# Publish only repository entities
php artisan easypack:publish --entities

# Publish all controllers (API + Admin + Auth)
php artisan easypack:publish --controllers

# Force overwrite existing files
php artisan easypack:publish --customizable --force
```

#### Using Vendor Publish Tags

Alternatively, use Laravel's native vendor:publish:

```bash
# Publish User model
php artisan vendor:publish --tag=easypack-models

# Publish API controllers
php artisan vendor:publish --tag=easypack-api-controllers

# Publish Admin controllers
php artisan vendor:publish --tag=easypack-admin-controllers

# Publish Web Auth controllers
php artisan vendor:publish --tag=easypack-auth-controllers

# Publish all controllers
php artisan vendor:publish --tag=easypack-controllers

# Publish repository entities
php artisan vendor:publish --tag=easypack-entities

# Publish everything customizable
php artisan vendor:publish --tag=easypack-customizable
```

#### Enable Local Controllers

After publishing controllers, enable them in your `.env` file:

```env
# Enable all local controllers
EASYPACK_USE_LOCAL_CONTROLLERS=true

# Or selectively enable API/Admin
EASYPACK_USE_LOCAL_API_CONTROLLERS=true
EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true
```

Or configure individual controller overrides in `config/easypack.php`:

```php
'local_api_controllers' => [
    'auth' => \App\Http\Controllers\Api\AuthController::class,
    'profile' => \App\Http\Controllers\Api\ProfileController::class,
    // Add only controllers you want to customize
],

'local_admin_controllers' => [
    'dashboard' => \App\Http\Controllers\Admin\DashboardController::class,
    'users' => \App\Http\Controllers\Admin\ManageUsersController::class,
],
```

#### Customizing the User Model

The published User model extends `EasyPack\Starter\Models\User`:

```php
// app/Models/User.php
namespace App\Models;

use EasyPack\Starter\Models\User as BaseUser;

class User extends BaseUser
{
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'timezone',
    ];

    // Add your custom methods
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
```

Update `config/easypack.php` to use your model:

```php
'user_model' => \App\Models\User::class,
```

#### Customizing Controllers

Published controllers extend package controllers, allowing you to:

- Override specific methods
- Add custom logic before/after parent calls
- Add new endpoints

```php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use EasyPack\Starter\Http\Controllers\Api\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
    public function login(Request $request): JsonResponse
    {
        // Add pre-login logic
        Log::info('Login attempt', ['email' => $request->email]);

        // Call parent login
        $response = parent::login($request);

        // Add post-login logic
        // ...

        return $response;
    }

    // Add custom endpoints
    public function loginWithGoogle(Request $request): JsonResponse
    {
        // Your social login implementation
    }
}
```

#### Using Repository Entities

Published repositories extend `EasyPack\Starter\Entities\BaseRepository`:

```php
// app/Entities/Users/UsersRepository.php
$usersRepo = app(UsersRepository::class);

// Find by email
$user = $usersRepo->findByEmail('user@example.com');

// Get users in role
$admins = $usersRepo->getUsersInRole(['admin', 'super-admin']);

// Search with pagination
$users = $usersRepo->searchForAdmin($request);

// Disable/Enable users
$usersRepo->disable($user);
$usersRepo->enable($user);
```

### Authentication API

```bash
# Register
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password",
    "device_id": "unique-device-id",
    "device_type": "apple|android|web",
    "device_push_token": "optional-fcm-token"
}

# Login
POST /api/auth/login
{
    "email": "john@example.com",
    "password": "password",
    "device_id": "unique-device-id",
    "device_type": "apple",
    "device_push_token": "optional-fcm-token"
}

# Get current user
GET /api/auth/me
Authorization: Bearer {token}

# Logout
POST /api/auth/logout
Authorization: Bearer {token}

# Logout all devices
POST /api/auth/logout-all
Authorization: Bearer {token}
```

### Profile API

```bash
# Get profile
GET /api/profile

# Update profile
PUT /api/profile
{
    "name": "New Name",
    "email": "newemail@example.com"
}

# Update password
PUT /api/profile/password
{
    "current_password": "old-password",
    "password": "new-password",
    "password_confirmation": "new-password"
}

# Get devices
GET /api/profile/devices

# Logout from specific device
DELETE /api/profile/devices/{deviceId}
```

### Scaffolding Commands

Generate complete CRUD:

```bash
# Generate everything
php artisan make:easypack:crud Product --all

# Generate specific components
php artisan make:easypack:model Product
php artisan make:easypack:repository Product
php artisan make:easypack:api-controller Product
php artisan make:easypack:admin-controller Product
```

### Settings Manager

```php
use EasyPack\Starter\Facades\Setting;

// Get a setting
$value = Setting::get('app_name', 'Default App');

// Set a setting
Setting::set('app_name', 'My App');

// Check if setting exists
Setting::has('app_name');

// Delete a setting
Setting::forget('app_name');
```

Or use helper functions:

```php
$value = setting('app_name', 'Default');
setting_set('app_name', 'New Value');
```

### Navigator Service

Build dynamic navigation menus:

```php
use EasyPack\Starter\Facades\Navigator;

// Add navigation item
Navigator::addItem([
    'text' => 'Dashboard',
    'icon_class' => 'fas fa-home',
    'resource' => 'dashboard',
    'order' => 1,
], 'sidebar');

// Get navigation items
$items = Navigator::getNavigation('sidebar');
```

### Response Macros

```php
// Success response
return response()->apiSuccess($data, 'Operation successful');

// Error response
return response()->apiError('Something went wrong', 400);

// Paginated response
return response()->apiSuccessPaginated($paginator);

// Not found
return response()->apiNotFound('User not found');

// Validation error
return response()->apiValidationError($validator->errors());

// Unauthorized
return response()->apiUnauthorized('Invalid token');
```

### Push Notifications

```php
use EasyPack\Starter\Models\NotificationBuilder;

// Send to user
NotificationBuilder::create()
    ->title('Hello!')
    ->message('This is a test notification')
    ->toUser($user)
    ->save();

// Send to topic
NotificationBuilder::create()
    ->title('Announcement')
    ->message('New feature available')
    ->toTopic('announcements')
    ->save();

// Schedule notification
NotificationBuilder::create()
    ->title('Reminder')
    ->message('Don\'t forget!')
    ->toUser($user)
    ->delay(60) // 60 minutes from now
    ->save();
```

Process pending notifications:

```bash
php artisan easypack:send-notifications
```

### Media Attachments

```php
use EasyPack\Starter\Traits\HasMediaAttachments;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, HasMediaAttachments;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('documents');
    }
}

// Usage
$product->addMediaFile($request->file('image'), 'images');
$product->getMediaUrls('images');
$product->getFirstMediaUrl('images', 'thumb');
```

## Common Workflows

### 1. Creating a New Resource (CRUD)

To create a full set of components for a new resource (e.g., "Product"), use the CRUD generator:

```bash
php artisan make:easypack:crud Product --all
```

This will generate:
- `app/Models/Product.php`
- `app/Entities/Products/ProductsRepository.php`
- `app/Http/Controllers/Api/ProductController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- Migration file (you'll need to edit this)

**Step-by-step:**

1.  **Run the command:** `php artisan make:easypack:crud Product --all`
2.  **Edit Migration:** Open the generated migration file in `database/migrations` and add your columns.
3.  **Run Migration:** `php artisan migrate`
4.  **Register Routes:** Add routes in `routes/api.php`:
    ```php
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    ```
5.  **Generate Docs:** `php artisan generate:docs` to see your new endpoints in Swagger.

### 2. Generating and Testing Documentation

Easy Pack can automatically generate Swagger/OpenAPI documentation and even create tests based on it.

**Generate Documentation Only:**

```bash
php artisan generate:docs
```
View at: `http://your-app.test/docs/swagger.html`

**Generate Tests from Documentation:**

Once you have documentation, you can generate PHPUnit tests that verify your API matches the docs:

```bash
php artisan generate:api-tests
```

**The "All-in-One" Command:**

To generate docs, generate tests, and run them all in one go (great for CI/CD):

```bash
php artisan generate:docs-tests
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `easypack:install` | Install the package and publish assets |
| `easypack:publish` | Publish customizable files (models, controllers, entities) |
| `easypack:publish --customizable` | Publish all customizable files |
| `easypack:publish --models` | Publish User model |
| `easypack:publish --api-controllers` | Publish API controllers |
| `easypack:publish --admin-controllers` | Publish Admin controllers |
| `easypack:publish --auth-controllers` | Publish Web Auth controllers |
| `easypack:publish --entities` | Publish repository entities |
| `easypack:publish --enable` | Also enable local controllers in .env |
| `easypack:purge-tokens` | Remove expired access tokens |
| `easypack:purge-notifications` | Remove old notifications |
| `easypack:send-notifications` | Process and send pending push notifications |
| `make:easypack:model` | Generate a model with repository pattern |
| `make:easypack:repository` | Generate a repository class |
| `make:easypack:api-controller` | Generate an API controller |
| `make:easypack:admin-controller` | Generate an admin controller |
| `make:easypack:crud` | Generate complete CRUD scaffolding |
| `generate:docs` | Generate API documentation (Swagger/Postman) |
| `generate:api-tests` | Generate API tests from documentation |
| `generate:docs-tests` | Generate API Documentation, API Tests, and Run Tests |

## Configuration Options

### config/easypack.php

```php
return [
    'load_web_routes' => true,
    'token_expiry_days' => 90,
    // ...
];
```

### config/features.php

```php
return [
    'registration' => true,
    'account_deletion' => true,
    'push_notifications' => true,
    // ...
];
```

### config/push-notifications.php

```php
return [
    'firebase' => [
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
    ],
    'settings' => [
        'retention_days' => 90,
    ],
    // ...
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security vulnerabilities, please send an email to security@example.com.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
