# View Customization Guide

Complete guide to customizing Easy Pack's Blade views for your application's unique design and functionality requirements.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Publishing Views](#publishing-views)
- [View Structure](#view-structure)
- [Customization Strategies](#customization-strategies)
- [Upgrading Safely](#upgrading-safely)
- [Common Customizations](#common-customizations)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

Easy Pack uses Laravel's **view namespace** feature, which allows you to customize any view without modifying package files. All views use the `easypack::` namespace.

### How It Works

```php
// In controllers
return view('easypack::manage.pages.index');
```

**Laravel's Resolution Order**:
1. ✅ **Your customized views**: `resources/views/vendor/easypack/manage/pages/index.blade.php`
2. ⬇️ **Package views**: `vendor/easypack/starter/resources/views/manage/pages/index.blade.php`

This means:
- ✅ You can safely edit published views
- ✅ Package updates won't overwrite your customizations
- ✅ You can delete published views to use package defaults
- ✅ Mix and match: customize some views, use package defaults for others

---

## Quick Start

### 1. Publish Views You Want to Customize

```bash
# During installation (automatic)
php artisan easypack:install

# After installation - publish all management views
php artisan vendor:publish --tag=easypack-manage-views

# Or publish everything
php artisan vendor:publish --tag=easypack-views
```

### 2. Edit Published Views

```bash
# Views are now in resources/views/vendor/easypack/
nano resources/views/vendor/easypack/manage/pages/index.blade.php
```

### 3. Changes Reflect Immediately

Visit your application - changes are live!

---

## Publishing Views

### Available Publishing Tags

| Tag | What It Publishes | When to Use |
|-----|-------------------|-------------|
| `easypack-page-views` | Page management & public pages | Already done during install |
| `easypack-manage-views` | All management interfaces (users, roles, etc.) | **Recommended for full customization** |
| `easypack-views` | Everything (all views) | Complete control over all views |

### Publishing Commands

```bash
# Publish all management views (recommended)
php artisan vendor:publish --tag=easypack-manage-views

# Publish all Easy Pack views
php artisan vendor:publish --tag=easypack-views

# Force re-publish (overwrites your changes!)
php artisan vendor:publish --tag=easypack-views --force

# Publish with provider (more control)
php artisan vendor:publish --provider="EasyPack\EasyPackServiceProvider" --tag=easypack-manage-views
```

---

## View Structure

### Published Directory Structure

After publishing `easypack-manage-views`, your structure looks like:

```
resources/views/vendor/easypack/
├── manage/
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── users/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   ├── show.blade.php
│   │   └── edit-password.blade.php
│   ├── roles/
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   └── users.blade.php
│   ├── permissions/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── pages/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── push-notifications/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── show.blade.php
│   ├── invitations/
│   │   ├── index.blade.php
│   │   └── create.blade.php
│   ├── devices/
│   │   └── index.blade.php
│   └── documentation/
│       └── index.blade.php
├── pages/
│   ├── privacy.blade.php
│   ├── terms.blade.php
│   └── contact-us.blade.php
├── layouts/
│   ├── app.blade.php
│   ├── master-frontend-internal.blade.php
│   └── partials/
│       ├── header.blade.php
│       ├── sidebar.blade.php
│       └── footer.blade.php
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
└── emails/
    ├── invitation.blade.php
    └── push-notification.blade.php
```

### View Dependencies

Most management views extend: `@extends('easypack::layouts.app')`
- Uses Bootstrap 5
- Font Awesome icons
- Alpine.js for interactivity
- Quill editor for rich text (pages)

---

## Customization Strategies

### Strategy 1: Selective Customization (Recommended)

Only publish and customize what you need. Unpublished views use package defaults.

```bash
# Publish only management views
php artisan vendor:publish --tag=easypack-manage-views

# Edit only what you need
nano resources/views/vendor/easypack/manage/pages/index.blade.php

# Delete views you don't want to customize
rm resources/views/vendor/easypack/manage/users/index.blade.php
# (will use package default)
```

**Pros**: 
- ✅ Easy upgrades (fewer conflicts)
- ✅ Get bug fixes automatically for non-customized views
- ✅ Less maintenance

**Cons**:
- ⚠️ Mixed customization levels

### Strategy 2: Full Customization

Publish everything and customize to your exact needs.

```bash
# Publish all views
php artisan vendor:publish --tag=easypack-views

# Customize everything
# Your app, your rules!
```

**Pros**:
- ✅ Complete control
- ✅ Consistent design across all views

**Cons**:
- ⚠️ More files to maintain
- ⚠️ Manual merging needed on package updates

### Strategy 3: Layout Override

Customize only the layout, keep views unchanged.

```bash
# Publish views
php artisan vendor:publish --tag=easypack-views

# Edit only the layout
nano resources/views/vendor/easypack/layouts/app.blade.php

# All management views will use your custom layout
```

**Pros**:
- ✅ Consistent branding with minimal effort
- ✅ Individual views still get updates

**Cons**:
- ⚠️ Layout changes in package might break things

---

## Upgrading Safely

### Before Upgrading Easy Pack

1. **Commit your customizations**
   ```bash
   git add resources/views/vendor/easypack/
   git commit -m "Current view customizations"
   ```

2. **Backup published views**
   ```bash
   cp -r resources/views/vendor/easypack resources/views/vendor/easypack.backup
   ```

3. **Note what you customized**
   ```bash
   # See what you've changed
   git diff vendor/easypack/starter/resources/views/ resources/views/vendor/easypack/
   ```

### After Upgrading Easy Pack

1. **Check for view changes in package**
   ```bash
   # Compare package views with your customized versions
   diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
   ```

2. **Review changelog**
   ```bash
   # Check what changed in views
   cat vendor/easypack/starter/CHANGELOG.md | grep -i "view\|blade"
   ```

3. **Merge changes if needed**
   ```bash
   # Option 1: Use a diff tool
   meld resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
   
   # Option 2: Re-publish specific views and manually merge
   php artisan vendor:publish --tag=easypack-manage-views --force
   # Then use git to see what changed and restore your customizations
   ```

### Upgrade Warning Feature

Easy Pack will warn you if published views might be outdated:

```bash
php artisan easypack:check-views
# ⚠️  Warning: 3 published views may be outdated
# 
# Updated in package:
#   - manage/users/index.blade.php
#   - manage/roles/edit.blade.php
#   - layouts/app.blade.php
# 
# To update:
#   1. Review changes: diff resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
#   2. Backup: cp -r resources/views/vendor/easypack resources/views/vendor/easypack.backup
#   3. Re-publish: php artisan vendor:publish --tag=easypack-views --force
#   4. Restore customizations using version control
```

---

## Common Customizations

### 1. Change Page Layout/Design

**Goal**: Match your application's design system

```blade
<!-- resources/views/vendor/easypack/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - @yield('pageTitle', 'Admin')</title>
    
    <!-- Your CSS framework instead of Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3/dist/tailwind.min.css" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <!-- Your navbar/header -->
    @include('layouts.admin-header')
    
    <div class="container">
        @yield('content')
    </div>
    
    <!-- Your footer -->
    @include('layouts.admin-footer')
    
    @stack('scripts')
</body>
</html>
```

### 2. Add Company Branding

```blade
<!-- resources/views/vendor/easypack/manage/pages/index.blade.php -->

@extends('easypack::layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Add your logo -->
    <div class="mb-4">
        <img src="{{ asset('img/company-logo.png') }}" alt="Company Logo" class="h-12">
    </div>
    
    <!-- Customize breadcrumb style -->
    <nav aria-label="breadcrumb" class="custom-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Manage Pages</li>
        </ol>
    </nav>
    
    <!-- Rest of the view... -->
</div>
@endsection
```

### 3. Change Table Styling

```blade
<!-- resources/views/vendor/easypack/manage/users/index.blade.php -->

<!-- Replace Bootstrap table with your preferred style -->
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($users as $user)
        <tr>
            <td class="px-6 py-4">{{ $user->name }}</td>
            <td class="px-6 py-4">{{ $user->email }}</td>
            <td class="px-6 py-4">
                <a href="{{ route('manage.users.edit', $user) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

### 4. Add Custom Fields to Forms

```blade
<!-- resources/views/vendor/easypack/manage/pages/create.blade.php -->

<!-- Add after existing fields -->
<div class="mb-3">
    <label for="meta_description" class="form-label">Meta Description (SEO)</label>
    <textarea 
        class="form-control @error('meta_description') is-invalid @enderror" 
        id="meta_description" 
        name="meta_description" 
        rows="2"
    >{{ old('meta_description') }}</textarea>
    @error('meta_description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="featured_image" class="form-label">Featured Image</label>
    <input 
        type="file" 
        class="form-control @error('featured_image') is-invalid @enderror" 
        id="featured_image" 
        name="featured_image"
    >
    @error('featured_image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### 5. Change Rich Text Editor

Replace Quill with TinyMCE or CKEditor:

```blade
<!-- resources/views/vendor/easypack/manage/pages/create.blade.php -->

@push('styles')
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>
@endpush

@push('scripts')
<script>
    tinymce.init({
        selector: '#content',
        height: 500,
        plugins: 'link image code table lists',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code'
    });
    
    // Sync content before form submit
    document.querySelector('form').addEventListener('submit', function() {
        tinymce.triggerSave();
    });
</script>
@endpush
```

### 6. Add Permissions/Authorization to Views

```blade
<!-- resources/views/vendor/easypack/manage/pages/index.blade.php -->

<!-- Only show delete button to super admins -->
@can('delete', $page)
<form action="{{ route('manage.pages.destroy', $page->slug) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
        <i class="fas fa-trash"></i> Delete
    </button>
</form>
@endcan

<!-- Show different actions based on roles -->
@role('super-admin')
<a href="{{ route('manage.pages.advanced-settings', $page) }}" class="btn btn-warning">
    Advanced Settings
</a>
@endrole
```

### 7. Internationalization (i18n)

```blade
<!-- resources/views/vendor/easypack/manage/pages/index.blade.php -->

<h1>{{ __('pages.title') }}</h1>

<a href="{{ route('manage.pages.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> {{ __('pages.create_new') }}
</a>

<table>
    <thead>
        <tr>
            <th>{{ __('pages.table.title') }}</th>
            <th>{{ __('pages.table.slug') }}</th>
            <th>{{ __('pages.table.status') }}</th>
            <th>{{ __('pages.table.actions') }}</th>
        </tr>
    </thead>
</table>
```

Create language files:
```php
// lang/en/pages.php
return [
    'title' => 'Manage Pages',
    'create_new' => 'Create New Page',
    'table' => [
        'title' => 'Page Title',
        'slug' => 'Slug',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
];
```

---

## Best Practices

### ✅ DO

1. **Use Version Control**
   ```bash
   git add resources/views/vendor/easypack/
   git commit -m "Customize page management views"
   ```

2. **Document Your Changes**
   ```blade
   {{-- CUSTOMIZATION: Added company branding - 2024-12-08 --}}
   <div class="company-header">
       <img src="{{ asset('img/logo.png') }}" alt="Logo">
   </div>
   ```

3. **Keep Package Views as Reference**
   ```bash
   # Before editing, check the original
   cat vendor/easypack/starter/resources/views/manage/pages/index.blade.php
   ```

4. **Test After Updates**
   ```bash
   # After updating Easy Pack
   php artisan test
   # Manually test customized views in browser
   ```

5. **Use Blade Components for Reusability**
   ```blade
   <!-- Create: resources/views/components/admin-card.blade.php -->
   <div class="card shadow-sm">
       <div class="card-header">{{ $title }}</div>
       <div class="card-body">{{ $slot }}</div>
   </div>
   
   <!-- Use in customized views -->
   <x-admin-card title="Pages">
       <!-- Page content -->
   </x-admin-card>
   ```

### ❌ DON'T

1. **Don't Edit Package Views Directly**
   ```bash
   # ❌ WRONG - changes lost on composer update
   nano vendor/easypack/starter/resources/views/manage/pages/index.blade.php
   
   # ✅ CORRECT - publish first, then edit
   php artisan vendor:publish --tag=easypack-manage-views
   nano resources/views/vendor/easypack/manage/pages/index.blade.php
   ```

2. **Don't Blindly Use --force**
   ```bash
   # ❌ WRONG - overwrites your customizations
   php artisan vendor:publish --tag=easypack-views --force
   
   # ✅ CORRECT - check diff first
   diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/
   # Then manually merge changes
   ```

3. **Don't Remove Core Functionality**
   ```blade
   <!-- ❌ WRONG - breaks form submission -->
   <form action="{{ route('manage.pages.store') }}" method="POST">
       <!-- Missing @csrf - security vulnerability! -->
       <input name="title" value="">
   </form>
   
   <!-- ✅ CORRECT - keep CSRF protection -->
   <form action="{{ route('manage.pages.store') }}" method="POST">
       @csrf
       <input name="title" value="">
   </form>
   ```

4. **Don't Ignore Validation Errors**
   ```blade
   <!-- ❌ WRONG - no error display -->
   <input name="title" class="form-control">
   
   <!-- ✅ CORRECT - show validation errors -->
   <input name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}">
   @error('title')
       <div class="invalid-feedback">{{ $message }}</div>
   @enderror
   ```

---

## Troubleshooting

### Views Not Updating

**Problem**: Changes to published views don't appear

**Solutions**:
```bash
# Clear view cache
php artisan view:clear

# Clear all caches
php artisan cache:clear
php artisan config:clear

# Check file permissions
ls -la resources/views/vendor/easypack/

# Verify you're editing the right file
php artisan view:cache
# Then check storage/framework/views/ for compiled view location
```

### View Not Found Error

**Problem**: `View [easypack::manage.pages.index] not found`

**Solutions**:
```bash
# Re-publish views
php artisan vendor:publish --tag=easypack-views

# Check service provider is loaded
php artisan package:discover

# Verify package is installed
composer show easypack/starter

# Clear caches
php artisan view:clear
php artisan config:clear
```

### Styles/Scripts Not Loading

**Problem**: CSS/JS broken after customization

**Solutions**:
```blade
<!-- Ensure layout is properly extended -->
@extends('easypack::layouts.app')

<!-- Use correct stack names -->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/custom.js') }}"></script>
@endpush

<!-- Verify asset paths -->
{{ asset('css/app.css') }}  <!-- Correct -->
/css/app.css                <!-- Might not work with subdirectories -->
```

### Forms Not Submitting

**Problem**: Form submission fails or redirects incorrectly

**Solutions**:
```blade
<!-- Ensure CSRF token is present -->
@csrf

<!-- Use correct HTTP method -->
@method('PUT')  <!-- For update routes -->
@method('DELETE')  <!-- For delete routes -->

<!-- Verify route names -->
{{ route('manage.pages.store') }}  <!-- Check route:list -->

<!-- Check form encoding for file uploads -->
<form enctype="multipart/form-data">
```

### Package Updates Break Views

**Problem**: Views break after `composer update`

**Solutions**:
```bash
# Check what changed
cat vendor/easypack/starter/CHANGELOG.md

# Compare views
diff -r resources/views/vendor/easypack/ vendor/easypack/starter/resources/views/

# Use git to restore customizations
git checkout resources/views/vendor/easypack/manage/pages/index.blade.php

# Or start fresh and re-apply customizations
mv resources/views/vendor/easypack resources/views/vendor/easypack.old
php artisan vendor:publish --tag=easypack-views
# Then manually merge customizations
```

---

## Additional Resources

- **Main Documentation**: [README.md](README.md)
- **Installation Guide**: [INSTALLATION.md](INSTALLATION.md)
- **Page Management**: [PAGE_MANAGEMENT.md](PAGE_MANAGEMENT.md)
- **API Documentation**: [API_KEY_AUTH.md](API_KEY_AUTH.md)
- **Developer Guide**: [DEVELOPER_GUIDE_PAGES.md](DEVELOPER_GUIDE_PAGES.md)
- **Laravel Views Documentation**: https://laravel.com/docs/views
- **Laravel Blade Documentation**: https://laravel.com/docs/blade

---

## Getting Help

If you encounter issues with view customization:

1. Check this guide's [Troubleshooting](#troubleshooting) section
2. Review the [Laravel Blade documentation](https://laravel.com/docs/blade)
3. Check Easy Pack's [CHANGELOG.md](CHANGELOG.md) for breaking changes
4. Compare your customizations with package defaults
5. Open an issue on GitHub with:
   - Easy Pack version
   - Laravel version
   - What you customized
   - Error messages or unexpected behavior

---

**Happy Customizing! 🎨**
