# Upgrade Guide

This document describes how to upgrade Easy Pack between major versions and how to handle Laravel/dependency upgrades.

## Upgrading Existing Projects to Use Local Controllers

If you installed Easy Pack before version 1.1 (when controllers were not auto-published), you can enable local controllers to customize API endpoints:

### Quick Upgrade

```bash
# Publish all controllers and enable them
php artisan easypack:publish --controllers --enable
```

### Step-by-Step Upgrade

1. **Publish the controllers you need:**
   ```bash
   # All controllers (API + Admin + Auth)
   php artisan easypack:publish --controllers
   
   # Or selectively:
   php artisan vendor:publish --tag=easypack-api-controllers    # 5 API controllers
   php artisan vendor:publish --tag=easypack-admin-controllers  # 4 Admin controllers
   php artisan vendor:publish --tag=easypack-auth-controllers   # 2 Auth controllers
   ```

2. **Enable local controllers in `.env`:**
   ```dotenv
   # Add these lines to your .env file
   EASYPACK_USE_LOCAL_API_CONTROLLERS=true
   EASYPACK_USE_LOCAL_ADMIN_CONTROLLERS=true
   ```

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   ```

4. **Verify routes are using local controllers:**
   ```bash
   php artisan route:list --path=api
   # Should show: App\Http\Controllers\Api\AuthController
   # Instead of: Oxygen\Starter\Http\Controllers\Api\AuthController
   ```

### Published Controller Locations

| Controller Type | Count | Location |
|-----------------|-------|----------|
| API Controllers | 5 | `app/Http/Controllers/Api/` |
| Admin Controllers | 4 | `app/Http/Controllers/Admin/` |
| Auth Controllers | 2 | `app/Http/Controllers/Auth/` |

**API Controllers:**
- `AuthController.php` - Login, register, logout
- `ProfileController.php` - User profile management
- `GuestController.php` - Guest/public endpoints
- `ForgotPasswordController.php` - Password reset
- `DeviceController.php` - Device/push notification management

**Admin Controllers:**
- `DashboardController.php` - Admin dashboard
- `ManageUsersController.php` - User management
- `ManageRolesController.php` - Role management
- `ManagePermissionsController.php` - Permission management

**Auth Controllers:**
- `LoginController.php` - Web login (admin panel)
- `ProfileController.php` - Web profile management

---

## Version Compatibility Matrix

| Easy Pack | Laravel | PHP | Sanctum | Spatie Permission | Spatie Media |
|----------------|---------|-----|---------|-------------------|--------------|
| 1.x            | 11.x, 12.x | ^8.2 | ^4.0 | ^5.0, ^6.0 | ^10.0, ^11.0 |
| 2.x (future)   | 12.x, 13.x | ^8.3 | ^4.0, ^5.0 | ^6.0, ^7.0 | ^11.0, ^12.0 |

## Checking Compatibility

Use the built-in version helpers to check your environment:

```php
// Get current versions
$info = oxygen_compatibility();
// Returns: ['oxygen' => '1.0.0', 'laravel' => '12.0.0', ...]

// Check Laravel version
if (oxygen_is_laravel(12)) {
    // Laravel 12 specific code
}

// Check if a feature is supported
if (oxygen_supports('permission-v6')) {
    // Spatie Permission v6 specific code
}

// Check minimum version
if (oxygen_laravel_at_least('12.5')) {
    // Requires Laravel 12.5+
}
```

---

## Upgrading Laravel (Same Oxygen Version)

### Laravel 11 → Laravel 12

1. **Update Laravel framework**
   ```bash
   composer require laravel/framework:^12.0
   ```

2. **No Oxygen changes required** - Oxygen 1.x supports both Laravel 11 and 12

3. **Run migrations** (if any new Laravel migrations)
   ```bash
   php artisan migrate
   ```

### Laravel 12 → Laravel 13 (Future)

When Laravel 13 is released:

1. **Check for Oxygen update first**
   ```bash
   composer show easypack/starter
   ```

2. **If Oxygen 1.x supports Laravel 13:**
   ```bash
   composer require laravel/framework:^13.0
   ```

3. **If Oxygen 2.x is required:**
   ```bash
   composer require easypack/starter:^2.0 laravel/framework:^13.0
   ```

---

## Upgrading Spatie Packages

### Spatie Permission 5.x → 6.x

Oxygen 1.x supports both versions. To upgrade:

```bash
composer require spatie/laravel-permission:^6.0
```

**Breaking changes handled by Oxygen:**
- UUID support is automatic via abstraction layer
- Guard name handling is consistent

### Spatie Media Library 10.x → 11.x

Oxygen 1.x supports both versions. To upgrade:

```bash
composer require spatie/laravel-medialibrary:^11.0
```

**Note:** Run any new Spatie migrations:
```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

---

## Upgrading Easy Pack

### 1.x → 2.x (Future Major Upgrade)

When Oxygen 2.0 is released (for Laravel 13+), follow these steps:

#### Step 1: Backup

```bash
# Backup your database
php artisan backup:run

# Backup custom oxygen files
cp -r config/easypack.php config/easypack.php.backup
cp -r config/features.php config/features.php.backup
```

#### Step 2: Update Composer

```bash
composer require easypack/starter:^2.0
```

#### Step 3: Publish New Config (if changed)

```bash
php artisan vendor:publish --provider="Oxygen\Starter\EasyPackServiceProvider" --tag="oxygen-config" --force
```

Compare with your backup and merge any custom settings.

#### Step 4: Run Migrations

```bash
php artisan migrate
```

#### Step 5: Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Step 6: Test Your Application

```bash
php artisan test
```

---

## Using Abstraction Layers

Oxygen provides abstraction interfaces to protect against breaking changes in dependencies. Use these instead of direct package calls:

### Token Operations

```php
use Oxygen\Starter\Contracts\TokenRepositoryInterface;

class MyController
{
    public function __construct(
        protected TokenRepositoryInterface $tokens
    ) {}

    public function createToken(Request $request)
    {
        $result = $this->tokens->createToken(
            $request->user(),
            'api-token',
            ['*'],
            ['device_id' => $request->device_id]
        );
        
        return $result['token'];
    }
}
```

### Permission Checks

```php
use Oxygen\Starter\Contracts\PermissionServiceInterface;

class MyController
{
    public function __construct(
        protected PermissionServiceInterface $permissions
    ) {}

    public function checkAccess(Request $request)
    {
        if ($this->permissions->hasRole($request->user(), 'admin')) {
            // Admin access
        }
        
        if ($this->permissions->hasPermission($request->user(), 'edit-posts')) {
            // Can edit posts
        }
    }
}
```

### Media Operations

```php
use Oxygen\Starter\Contracts\MediaServiceInterface;

class MyController
{
    public function __construct(
        protected MediaServiceInterface $media
    ) {}

    public function uploadFile(Request $request, Post $post)
    {
        $mediaItem = $this->media->addMedia(
            $post,
            $request->file('image'),
            'images',
            ['custom_properties' => ['alt' => 'My image']]
        );
        
        return $this->media->getUrl($mediaItem, 'thumb');
    }
}
```

---

## Conditional Version Code

When you need version-specific code:

```php
// For Laravel version differences
if (oxygen_is_laravel(12)) {
    // Laravel 12 specific implementation
} elseif (oxygen_is_laravel(11)) {
    // Laravel 11 specific implementation
}

// For package version differences
if (oxygen_supports('permission-v6')) {
    // Use Spatie Permission v6 features
} else {
    // Fallback for v5
}

// For PHP version differences
if (oxygen_supports('enums')) {
    // Use PHP 8.1+ enums
}
```

---

## Troubleshooting Upgrades

### Common Issues

#### 1. Class Not Found Errors

```bash
composer dump-autoload
php artisan package:discover
```

#### 2. Migration Errors

Check if migration already exists:
```bash
php artisan migrate:status
```

Skip specific migration:
```bash
php artisan migrate --exclude=create_personal_access_tokens_table
```

#### 3. Config Conflicts

Re-publish and compare:
```bash
php artisan vendor:publish --tag="oxygen-config" --force
```

#### 4. Route Conflicts

Clear route cache:
```bash
php artisan route:clear
php artisan route:list --path=api
```

### Getting Help

1. Check the [changelog](CHANGELOG.md) for breaking changes
2. Run `oxygen_compatibility()` to see version info
3. Open an issue on GitHub with:
   - Output of `oxygen_compatibility()`
   - Full error message
   - Steps to reproduce

---

## Versioning Policy

Easy Pack follows [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.x → 2.x): Breaking changes, Laravel major version support changes
- **MINOR** (1.0 → 1.1): New features, backward compatible
- **PATCH** (1.0.0 → 1.0.1): Bug fixes, security patches

### Support Timeline

| Version | Status | Laravel Support | End of Support |
|---------|--------|-----------------|----------------|
| 1.x     | Active | 11.x, 12.x      | When Laravel 11 EOL |
| 2.x     | Future | 12.x, 13.x      | TBD |

We aim to support:
- **Current Laravel version** + **Previous major version**
- Security patches for **one year** after a major version is superseded
