# 📄 Page Management System - Developer Guide

## Overview

Easy Pack includes a complete page management system that allows administrators to create, edit, and delete custom pages through an intuitive admin interface. When you install Easy Pack, all the necessary files are automatically published to your project for full customization.

## ✅ What's Automatically Installed

When you run `php artisan easypack:install`, the following files are published:

### 1. **Model** (Customizable)
```
app/Models/PageContent.php
```
- Extends `EasyPack\Models\PageContent`
- Fully customizable - add your own methods and relationships
- Already includes all necessary database interactions

### 2. **Routes** (Customizable)
```
routes/pages.php
```
- All page management routes
- Automatically included in `routes/web.php`
- Edit to customize URLs or add middleware

### 3. **Views** (Customizable)
```
resources/views/vendor/easypack/manage/pages/
├── index.blade.php    # List all pages
├── create.blade.php   # Create new page form
└── edit.blade.php     # Edit existing page

resources/views/vendor/easypack/pages/
├── privacy.blade.php  # Privacy Policy page
├── terms.blade.php    # Terms & Conditions page
└── contact-us.blade.php  # Contact Us page
```
- All Blade templates are published for full customization
- Modify layouts, styling, and functionality as needed

### 4. **Database**
```
database/migrations/2024_12_06_000001_create_page_contents_table.php
```
- Migration automatically runs during install
- Seeder creates default Privacy Policy and Terms pages

## 🚀 Features Available to Developers

### Pre-built Admin Interface

Admins can:
- ✅ **Create unlimited custom pages**
- ✅ **Edit all pages** with rich text editor (Quill)
- ✅ **Delete custom pages** (system pages protected)
- ✅ **Toggle page visibility** (active/inactive)
- ✅ **Auto-generate SEO-friendly slugs**
- ✅ **Full WYSIWYG editing** with formatting, images, links

### Developer Customization

You can:
- ✅ **Customize all views** - They're in your project
- ✅ **Modify routes** - Edit `routes/pages.php`
- ✅ **Extend the model** - Add custom methods to `PageContent`
- ✅ **Override controller** - Create your own `PageContentsController`
- ✅ **Add custom validation** - Modify form requests
- ✅ **Change permissions** - Edit middleware in routes

## 📍 Accessing the Admin Interface

### Default URLs:
- **List pages**: `/manage/pages`
- **Create page**: `/manage/pages/create`
- **Edit page**: `/manage/pages/{slug}/edit`

### Public Page URLs:
- **Privacy Policy**: `/privacy-policy`
- **Terms & Conditions**: `/terms-conditions`
- **Contact Us**: `/contact-us`
- **Custom pages**: `/{slug}`

### Login:
- **URL**: `/login`
- **Default Admin**:
  - Email: `admin@example.com`
  - Password: `password`

## 🛠️ Customization Examples

### 1. Customize the PageContent Model

Edit `app/Models/PageContent.php`:

```php
<?php

namespace App\Models;

use EasyPack\Models\PageContent as EasyPackPageContent;

class PageContent extends EasyPackPageContent
{
    /**
     * Get excerpt from content
     */
    public function getExcerpt($length = 150)
    {
        return substr(strip_tags($this->content), 0, $length) . '...';
    }

    /**
     * Scope to get only active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get word count
     */
    public function getWordCount()
    {
        return str_word_count(strip_tags($this->content));
    }

    /**
     * Check if page is a system page
     */
    public function isSystemPage()
    {
        return in_array($this->slug, ['privacy-policy', 'terms-conditions']);
    }
}
```

### 2. Customize Page Routes

Edit `routes/pages.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use EasyPack\Http\Controllers\Manage\PageContentsController;

Route::prefix('manage')->name('manage.')->middleware(['web', 'auth', 'role:admin|super-admin'])->group(function () {
    // Standard CRUD routes
    Route::resource('pages', PageContentsController::class)->except(['show']);
    
    // Custom routes
    Route::post('/pages/{slug}/duplicate', [PageContentsController::class, 'duplicate'])->name('pages.duplicate');
    Route::post('/pages/bulk-delete', [PageContentsController::class, 'bulkDelete'])->name('pages.bulk-delete');
});
```

### 3. Customize Views

Edit any view in `resources/views/vendor/easypack/manage/pages/`:

```blade
{{-- resources/views/vendor/easypack/manage/pages/index.blade.php --}}

@extends('easypack::layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Add custom header --}}
    <div class="custom-header">
        <h1>My Custom Page Manager</h1>
    </div>

    {{-- Your custom content here --}}
    @foreach($pages as $page)
        <div class="custom-page-card">
            <h3>{{ $page->title }}</h3>
            <p>{{ $page->getExcerpt() }}</p>
            <span>Words: {{ $page->getWordCount() }}</span>
        </div>
    @endforeach
</div>
@endsection
```

### 4. Add Custom Public Page Template

Create `resources/views/pages/custom-template.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{{ $page->title }}</title>
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body>
    <header>
        <h1>{{ config('app.name') }}</h1>
    </header>
    
    <main>
        <article>
            <h1>{{ $page->title }}</h1>
            <div class="content">
                {!! $page->content !!}
            </div>
        </article>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>
</body>
</html>
```

Then use it in a custom route:

```php
// routes/web.php
use App\Models\PageContent;

Route::get('/about-us', function () {
    $page = PageContent::getBySlug('about-us');
    return view('pages.custom-template', ['page' => $page]);
});
```

### 5. Create Custom Controller

If you need more control, create your own controller:

```php
<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use Illuminate\Http\Request;

class CustomPageController extends Controller
{
    public function index()
    {
        $pages = PageContent::active()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('manage.pages.index', compact('pages'));
    }

    public function duplicate(string $slug)
    {
        $page = PageContent::where('slug', $slug)->firstOrFail();
        
        $duplicate = $page->replicate();
        $duplicate->slug = $page->slug . '-copy';
        $duplicate->title = $page->title . ' (Copy)';
        $duplicate->save();

        return redirect()
            ->route('manage.pages.index')
            ->with('success', 'Page duplicated successfully!');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        PageContent::whereIn('id', $ids)
            ->whereNotIn('slug', ['privacy-policy', 'terms-conditions'])
            ->delete();

        return redirect()
            ->route('manage.pages.index')
            ->with('success', count($ids) . ' pages deleted!');
    }
}
```

Update routes to use your custom controller:

```php
// routes/pages.php
use App\Http\Controllers\Manage\CustomPageController;

Route::prefix('manage')->name('manage.')->middleware(['web', 'auth', 'role:admin|super-admin'])->group(function () {
    Route::get('/pages', [CustomPageController::class, 'index'])->name('pages.index');
    Route::post('/pages/{slug}/duplicate', [CustomPageController::class, 'duplicate'])->name('pages.duplicate');
    // ... other routes
});
```

## 🎨 Styling Customization

### Modify the Rich Text Editor

Edit `resources/views/vendor/easypack/manage/pages/create.blade.php` or `edit.blade.php`:

```javascript
// Customize Quill toolbar
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],  // Remove H4-H6
            ['bold', 'italic', 'underline'],   // Remove strike
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],                           // Remove image
            ['clean']
        ]
    }
});
```

### Custom CSS for Pages

Add to your `public/css/custom.css`:

```css
/* Custom page management styles */
.page-card {
    border: 2px solid #eee;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.page-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Custom editor styles */
.ql-editor {
    font-family: 'Your Custom Font', sans-serif;
    font-size: 16px;
    line-height: 1.6;
}
```

## 🔐 Security & Permissions

### Default Permissions

Page management requires `admin` or `super-admin` role:

```php
// routes/pages.php
Route::middleware(['web', 'auth', 'role:admin|super-admin'])->group(function () {
    // Routes here
});
```

### Custom Permissions

You can create custom permissions:

```php
// Database seeder
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'manage-pages']);
Permission::create(['name' => 'create-pages']);
Permission::create(['name' => 'edit-pages']);
Permission::create(['name' => 'delete-pages']);
```

Then update routes:

```php
Route::middleware(['web', 'auth', 'permission:manage-pages'])->group(function () {
    Route::get('/pages', [PageContentsController::class, 'index']);
    
    Route::middleware('permission:create-pages')->group(function () {
        Route::get('/pages/create', [PageContentsController::class, 'create']);
        Route::post('/pages', [PageContentsController::class, 'store']);
    });
    
    Route::middleware('permission:edit-pages')->group(function () {
        Route::get('/pages/{slug}/edit', [PageContentsController::class, 'edit']);
        Route::put('/pages/{slug}', [PageContentsController::class, 'update']);
    });
    
    Route::middleware('permission:delete-pages')->group(function () {
        Route::delete('/pages/{slug}', [PageContentsController::class, 'destroy']);
    });
});
```

## 📊 Database Structure

### Page Contents Table

```php
Schema::create('page_contents', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();  // URL-friendly identifier
    $table->string('title');           // Display title
    $table->text('content');           // HTML content
    $table->boolean('is_active')->default(true);  // Visibility
    $table->timestamps();
});
```

### Accessing Data

```php
// Get active pages
$pages = PageContent::where('is_active', true)->get();

// Get page by slug
$page = PageContent::getBySlug('about-us');

// Create new page
$page = PageContent::create([
    'slug' => 'company-history',
    'title' => 'Our Company History',
    'content' => '<p>Founded in 2020...</p>',
    'is_active' => true,
]);

// Update page
$page->update(['content' => '<p>Updated content...</p>']);

// Delete page
$page->delete();
```

## 🧪 Testing

### Feature Tests

Create `tests/Feature/PageManagementTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pages_list()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get('/manage/pages');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post('/manage/pages', [
                'slug' => 'test-page',
                'title' => 'Test Page',
                'content' => '<p>Test content</p>',
                'is_active' => true,
            ]);

        $response->assertRedirect('/manage/pages');
        $this->assertDatabaseHas('page_contents', [
            'slug' => 'test-page',
        ]);
    }

    public function test_public_can_view_active_page()
    {
        $page = PageContent::create([
            'slug' => 'public-page',
            'title' => 'Public Page',
            'content' => '<p>Public content</p>',
            'is_active' => true,
        ]);

        $response = $this->get('/public-page');
        $response->assertStatus(200);
    }
}
```

## 📚 API Integration (Optional)

Expose pages via API:

```php
// routes/api.php
use App\Models\PageContent;

Route::get('/pages', function () {
    return PageContent::where('is_active', true)
        ->select('slug', 'title', 'updated_at')
        ->get();
});

Route::get('/pages/{slug}', function ($slug) {
    $page = PageContent::getBySlug($slug);
    
    if (!$page) {
        return response()->json(['error' => 'Page not found'], 404);
    }
    
    return response()->json([
        'slug' => $page->slug,
        'title' => $page->title,
        'content' => $page->content,
        'updated_at' => $page->updated_at,
    ]);
});
```

## 🚀 Publishing Updates

If Easy Pack releases updates to the page management system, you can republish specific components:

```bash
# Republish all page management files
php artisan vendor:publish --tag=easypack-page-views --force
php artisan vendor:publish --tag=easypack-page-routes --force

# Or republish everything
php artisan vendor:publish --tag=easypack-customizable --force
```

## 📝 Best Practices

1. **Always extend the base model** - Don't modify Easy Pack files directly
2. **Keep system pages** - Privacy Policy and Terms are important
3. **Validate slugs** - Ensure uniqus are unique and URL-safe
4. **Sanitize HTML** - Use Purifier for user-generated content if needed
5. **Cache pages** - Consider caching for high-traffic pages
6. **Backup database** - Before major updates
7. **Test permissions** - Ensure proper access control

## 🐛 Troubleshooting

### Routes not working?
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Views not updating?
```bash
php artisan view:clear
```

### Database issues?
```bash
php artisan migrate:fresh --seed
```

### Permission errors?
```bash
# Regenerate permissions
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## 📖 Additional Resources

- [Laravel Routing](https://laravel.com/docs/routing)
- [Blade Templates](https://laravel.com/docs/blade)
- [Quill Editor](https://quilljs.com/docs/)
- [Spatie Permissions](https://spatie.be/docs/laravel-permission)

## ✅ Summary

Easy Pack's page management system gives you:
- ✅ Complete admin interface out of the box
- ✅ All files published to your project for customization
- ✅ Full control over routes, views, and models
- ✅ Rich text editing with Quill
- ✅ SEO-friendly URLs
- ✅ Permission-based access control

**You're ready to start customizing!** All files are in your project and can be modified to suit your needs.
