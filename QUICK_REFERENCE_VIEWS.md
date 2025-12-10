# Quick Reference: View Customization Commands

## Common Commands

### Publish Views
```bash
# Publish all management views (users, roles, permissions, pages, etc.)
php artisan vendor:publish --tag=easypack-manage-views

# Publish ALL Easy Pack views (everything)
php artisan vendor:publish --tag=easypack-views

# Publish only page management views (already done during install)
php artisan vendor:publish --tag=easypack-page-views

# Re-publish with force (WARNING: overwrites your changes!)
php artisan vendor:publish --tag=easypack-manage-views --force
```

### Check for Changes
```bash
# Compare your customized views with package defaults
diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/

# Check specific view
diff resources/views/vendor/easypack/manage/pages/index.blade.php \
     vendor/easypack/starter/resources/views/manage/pages/index.blade.php
```

### Clear Caches
```bash
# Clear view cache after making changes
php artisan view:clear

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Directory Structure

After publishing `easypack-manage-views`:

```
resources/views/vendor/easypack/
├── manage/
│   ├── dashboard/
│   ├── users/
│   ├── roles/
│   ├── permissions/
│   ├── pages/
│   ├── push-notifications/
│   ├── invitations/
│   ├── devices/
│   └── documentation/
├── layouts/
│   ├── app.blade.php
│   ├── master-frontend-internal.blade.php
│   └── partials/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   └── ...
└── pages/
    ├── privacy.blade.php
    ├── terms.blade.php
    └── contact-us.blade.php
```

## Quick Customization Workflow

1. **Publish views you want to customize**
   ```bash
   php artisan vendor:publish --tag=easypack-manage-views
   ```

2. **Edit views in your IDE**
   ```bash
   nano resources/views/vendor/easypack/manage/pages/index.blade.php
   ```

3. **Clear cache and test**
   ```bash
   php artisan view:clear
   # Visit your application and verify changes
   ```

4. **Commit your changes**
   ```bash
   git add resources/views/vendor/easypack/
   git commit -m "Customize page management views"
   ```

## Upgrade Workflow

1. **Before upgrading, commit current state**
   ```bash
   git add resources/views/vendor/easypack/
   git commit -m "Current view customizations before upgrade"
   ```

2. **Update Easy Pack**
   ```bash
   composer update easypack/starter
   ```

3. **Check for view changes**
   ```bash
   diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
   ```

4. **If views changed, merge manually**
   ```bash
   # Use a visual diff tool like meld
   meld resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
   
   # Or re-publish and restore your changes
   cp -r resources/views/vendor/easypack resources/views/vendor/easypack.backup
   php artisan vendor:publish --tag=easypack-manage-views --force
   # Then manually restore your customizations using git diff
   ```

## Common View Locations

| View Type | Path | Common Customizations |
|-----------|------|----------------------|
| Pages List | `manage/pages/index.blade.php` | Table styling, columns, filters |
| Page Create | `manage/pages/create.blade.php` | Form fields, validation, editor |
| Page Edit | `manage/pages/edit.blade.php` | Form fields, additional metadata |
| Users List | `manage/users/index.blade.php` | User table, actions, filters |
| Main Layout | `layouts/app.blade.php` | Branding, navigation, footer |
| Login | `auth/login.blade.php` | Login form, branding |
| Public Pages | `pages/*.blade.php` | Content templates |

## Important Files to Customize

### High Priority (Most Commonly Customized)
1. `layouts/app.blade.php` - Main admin layout (branding, navigation)
2. `manage/pages/*.blade.php` - Page management interface
3. `manage/users/index.blade.php` - User list interface
4. `auth/login.blade.php` - Login page

### Medium Priority
5. `layouts/partials/header.blade.php` - Header/navbar
6. `layouts/partials/sidebar.blade.php` - Sidebar navigation
7. `manage/roles/*.blade.php` - Role management
8. `manage/permissions/*.blade.php` - Permission management

### Low Priority
9. Other management views (notifications, devices, etc.)
10. Email templates
11. Public pages (unless actively using them)

## Troubleshooting

### Changes Not Showing
```bash
# Clear all caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Check file permissions
ls -la resources/views/vendor/easypack/
```

### View Not Found Error
```bash
# Re-publish views
php artisan vendor:publish --tag=easypack-manage-views

# Verify package is loaded
php artisan package:discover
```

### Styles/Scripts Broken
```bash
# Ensure you're extending the correct layout
@extends('easypack::layouts.app')

# Use correct asset paths
{{ asset('css/app.css') }}
```

## Documentation Links

- **Comprehensive Guide**: [VIEW_CUSTOMIZATION.md](easy-pack/VIEW_CUSTOMIZATION.md)
- **Installation Guide**: [INSTALLATION.md](easy-pack/INSTALLATION.md)
- **Main README**: [README.md](easy-pack/README.md)

## Need Help?

1. Check [VIEW_CUSTOMIZATION.md](easy-pack/VIEW_CUSTOMIZATION.md) troubleshooting section
2. Review Laravel Blade documentation
3. Check Easy Pack CHANGELOG.md for breaking changes
4. Open an issue on GitHub with reproduction steps

---

**Pro Tip**: Always use version control (git) for your view customizations!
