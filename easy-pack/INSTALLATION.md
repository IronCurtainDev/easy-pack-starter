# Easy Pack Installation Guide

A step-by-step guide to install and configure Easy Pack for your Laravel project.

## Table of Contents

- [Requirements](#requirements)
- [Quick Start (2 Commands!)](#quick-start-2-commands)
- [Local Development (Before Packagist)](#local-development-before-packagist)
- [Installation Options](#installation-options)
- [What Gets Installed](#what-gets-installed)
- [Customizing Views](#customizing-views)
- [Verifying Installation](#verifying-installation)
- [Upgrading Existing Projects](#upgrading-existing-projects)
- [Troubleshooting](#troubleshooting)

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 11.x or 12.x |
| MySQL | 5.7+ (default) |
| PostgreSQL | 9.6+ (alternative) |
| SQLite | 3.x (alternative) |

### Required PHP Extensions

- `pdo_mysql` (or `pdo_pgsql` / `pdo_sqlite`)
- `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`

---

## Quick Start (2 Commands!)

### From Packagist (when published)

```bash
# Step 1: Create Laravel + Install Oxygen
composer create-project laravel/laravel my-project && cd my-project
composer require easypack/starter

# Step 2: Run the installer (that's it!)
php artisan easypack:install --quick --db-name=my_database
```

The `--quick` flag uses MySQL defaults and only asks for essential database info.

### What happens automatically:

✅ Environment configured (.env)  
✅ All config files published  
✅ Migrations run  
✅ Database seeded (users, roles, permissions)  
✅ Models, Controllers, Entities published  
✅ Routes configured (Laravel 11+ auto-patched)  
✅ Sanctum configured  
✅ App key generated  
✅ Caches cleared  

**Start the server:**
```bash
php artisan serve
# Visit: http://localhost:8000/api/v1/guests
```

---

## Local Development (Before Packagist)

When developing Easy Pack locally (not yet on Packagist), use one of these methods:

### Method 1: One-Command Install Script (Recommended)

```bash
# From the easy-pack directory:
./install-local.sh my-project --db-name=my_db --quick
```

This script:
1. Creates a new Laravel project
2. Configures local composer repository
3. Installs easy-pack as a symlink
4. Runs full installation

**Script Options:**
```bash
./install-local.sh my-project \
    --db-name=my_database \
    --db-user=root \
    --db-password=secret \
    --quick \
    --with-docs
```

### Method 2: Manual Setup

**Step 1: Create Laravel project**
```bash
composer create-project laravel/laravel my-project
cd my-project
```

**Step 2: Add local repository to composer.json**

Edit `composer.json` and add the `repositories` section at the top:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/easy-pack",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        ...
    }
}
```

**Step 3: Install Easy Pack**
```bash
composer require easypack/starter:@dev
```

**Step 4: Run installation**
```bash
php artisan easypack:install --quick --db-name=my_database
```

---

## Installation Options

### Quick Mode (Recommended for Development)

```bash
php artisan easypack:install --quick --db-name=my_database
```

Uses MySQL defaults, minimal prompts. Perfect for local development.

### Interactive Mode (Full Control)

```bash
php artisan easypack:install
```

Prompts for all configuration options interactively.

### Non-Interactive (CI/CD)

```bash
php artisan easypack:install \
    --app-name="My API" \
    --db=mysql \
    --db-name=production_db \
    --db-user=app_user \
    --db-password=secret \
    --force
```

### All Available Options

| Option | Description | Default |
|--------|-------------|---------|
| `--quick` | Quick install with MySQL defaults | `false` |
| `--force` | Overwrite existing files | `false` |
| `--with-docs` | Generate API documentation | `false` |
| `--skip-migrations` | Skip database migrations | `false` |
| `--skip-seeders` | Skip database seeding | `false` |
| `--skip-controllers` | Skip publishing controllers | `false` |
| `--skip-admin-controllers` | Skip admin controllers | `false` |
| `--skip-auth-controllers` | Skip auth controllers | `false` |
| `--skip-routes` | Skip route configuration | `false` |
| `--app-name=` | Application name | Interactive |
| `--db=` | Database driver | `mysql` |
| `--db-host=` | Database host | `127.0.0.1` |
| `--db-port=` | Database port | `3306` |
| `--db-name=` | Database name | Interactive |
| `--db-user=` | Database username | `root` |
| `--db-password=` | Database password | Interactive |

---

## What Gets Installed

### Files Published

| Files | Location | Count |
|-------|----------|-------|
| Models | `app/Models/` | 7 |
| Entities | `app/Entities/` | 6 |
| API Controllers | `app/Http/Controllers/Api/V1/` | 5 |
| Admin Controllers | `app/Http/Controllers/Admin/` | 4 |
| Auth Controllers | `app/Http/Controllers/Auth/` | 2 |
| Config files | `config/` | 4 |
| Migrations | `database/migrations/` | 7 |
| Routes | `routes/api.php` | 1 |
| Swagger UI | `public/docs/` | 1 |

### Default Users Created

| Email | Role | Password |
|-------|------|----------|
| `test@example.com` | admin | `password` |
| `admin@example.com` | admin | `password` |
| `superadmin@example.com` | super-admin | `password` |

### API Routes Available

```
POST   /api/v1/auth/login
POST   /api/v1/auth/register
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
GET    /api/v1/guests
GET    /api/v1/profile
PUT    /api/v1/profile
PUT    /api/v1/profile/password
...and more
```

---

## Customizing Views

**🎨 Easy Pack provides all views for full customization!**

During installation, page management views are automatically published to `resources/views/vendor/easypack/`. You can customize any view to match your design requirements.

### Views Published Automatically

During `php artisan easypack:install`, these views are published:

- **Page Management** (`resources/views/vendor/easypack/manage/pages/`)
  - `index.blade.php` - List all pages
  - `create.blade.php` - Create new page
  - `edit.blade.php` - Edit existing page

- **Public Pages** (`resources/views/vendor/easypack/pages/`)
  - `privacy.blade.php` - Privacy policy
  - `terms.blade.php` - Terms & conditions
  - `contact-us.blade.php` - Contact page

### Publishing Additional Management Views

You can publish **all management views** for complete customization:

```bash
# Publish all Easy Pack views (users, roles, permissions, etc.)
php artisan vendor:publish --tag=easypack-views

# Or publish specific view categories
php artisan vendor:publish --tag=easypack-manage-views      # All management views
php artisan vendor:publish --tag=easypack-page-views        # Page views only (already done during install)
```

**Available Management Views** (after publishing `easypack-manage-views`):

- **Users** (`resources/views/vendor/easypack/manage/users/`)
  - `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
  
- **Roles** (`resources/views/vendor/easypack/manage/roles/`)
  - `create.blade.php`, `edit.blade.php`, `users.blade.php`
  
- **Permissions** (`resources/views/vendor/easypack/manage/permissions/`)
  - `index.blade.php`, `create.blade.php`, `edit.blade.php`
  
- **Push Notifications** (`resources/views/vendor/easypack/manage/push-notifications/`)
  - `index.blade.php`, `create.blade.php`, `show.blade.php`
  
- **Invitations** (`resources/views/vendor/easypack/manage/invitations/`)
  - `index.blade.php`, `create.blade.php`

- **Dashboard, Devices, Documentation** and more...

### How View Resolution Works

Laravel automatically checks for customized views first:

1. **First**: Checks `resources/views/vendor/easypack/` (your customized views)
2. **Fallback**: Uses package views from `vendor/easypack/starter/resources/views/`

This means you can:
- Edit published views safely
- Delete views you don't want to customize (package versions will be used)
- Update Easy Pack without losing customizations

### Re-publishing Views After Updates

When you update Easy Pack, you may want to see what changed in the views:

```bash
# View differences (compare before re-publishing)
diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/

# Re-publish with --force to overwrite (WARNING: loses customizations)
php artisan vendor:publish --tag=easypack-views --force

# Better approach: Merge changes manually or use version control
```

**💡 Best Practice**: Use version control (git) to track your customizations, making it easy to see and merge updates.

### Example Customization

After publishing, edit any view:

```bash
# Edit the pages list view
nano resources/views/vendor/easypack/manage/pages/index.blade.php
```

Your changes will be immediately reflected when you visit `/manage/pages`.

For detailed customization examples and best practices, see [VIEW_CUSTOMIZATION.md](VIEW_CUSTOMIZATION.md).

---

## Verifying Installation

### Check Routes

```bash
php artisan route:list --path=api/v1
```

### Test the API

```bash
# Start server
php artisan serve

# Test guest endpoint
curl http://localhost:8000/api/v1/guests

# Test login
curl -X POST http://localhost:8000/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@example.com","password":"password","device_id":"test","device_type":"web"}'
```

### View API Documentation

```bash
# Generate docs (if not done during install)
php artisan generate:docs

# Visit: http://localhost:8000/docs/swagger.html
```

---

## Upgrading Existing Projects

If you installed Easy Pack before the simplified installer:

```bash
# Update package
composer update easypack/starter

# Re-publish updated files
php artisan easypack:publish --controllers --enable --force

# Update routes
php artisan vendor:publish --tag=easypack-routes --force

# Clear caches
php artisan config:clear && php artisan route:clear
```

---

## Troubleshooting

### Database Connection Failed

```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:**
1. Ensure MySQL/PostgreSQL is running
2. Verify credentials in `.env`
3. Create database: `mysql -u root -p -e "CREATE DATABASE my_database;"`

### Tables Already Exist

```
SQLSTATE[42S01]: Table already exists
```

**Solution:**
```bash
# Fresh install (drops all tables)
php artisan migrate:fresh

# Or skip migrations during install
php artisan easypack:install --skip-migrations
```

### Routes Not Found

```
404 Not Found: /api/v1/auth/login
```

**Solution:**
1. Check `routes/api.php` exists
2. For Laravel 11+, verify `bootstrap/app.php` has:
   ```php
   ->withRouting(
       web: __DIR__.'/../routes/web.php',
       api: __DIR__.'/../routes/api.php',  // This line
   )
   ```
3. Clear route cache: `php artisan route:clear`

### Class Not Found

```
Class 'App\Models\User' not found
```

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Missing PHP Extensions

```
could not find driver
```

**Solution:**
```bash
# Ubuntu/Debian
sudo apt install php8.2-mysql php8.2-mbstring

# macOS with Homebrew
brew install php@8.2
```

---

## Available Commands

After installation, these commands are available:

```bash
# Publishing & Customization
php artisan easypack:publish --customizable  # Republish all files
php artisan easypack:publish --controllers   # Republish controllers only

# Code Generation
php artisan make:easypack:crud Post          # Generate full CRUD
php artisan make:easypack:model Post         # Generate model + repository
php artisan make:easypack:api-controller     # Generate API controller

# API Documentation
php artisan generate:docs                  # Generate Swagger/OpenAPI

# Maintenance
php artisan easypack:purge-tokens            # Remove expired tokens
php artisan easypack:purge-notifications     # Remove old notifications
```

---

## Need Help?

- 📖 [README.md](README.md) - Package overview
- 🔄 [UPGRADE.md](UPGRADE.md) - Version upgrades
- 📝 [CHANGELOG.md](CHANGELOG.md) - Recent changes
- 🐛 Report issues on GitHub
