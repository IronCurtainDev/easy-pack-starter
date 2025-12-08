# Page Management System

Easy Pack includes a built-in page management system that allows administrators to create and edit custom pages with a rich text editor.

## Features

- ✅ **Create custom pages** with a rich text editor (Quill)
- ✅ **Edit existing pages** including Privacy Policy and Terms & Conditions
- ✅ **Delete custom pages** (system pages are protected)
- ✅ **Toggle page visibility** (active/inactive)
- ✅ **SEO-friendly URLs** with customizable slugs
- ✅ **Rich formatting** - headers, lists, links, images, code blocks, etc.

## Accessing Page Management

1. **Login to Dashboard**: http://localhost:8000/login
   - Email: `admin@example.com`
   - Password: `password`

2. **Navigate to Pages**: Click "Pages" in the sidebar (between "Users" and "Roles")
   - Or visit: http://localhost:8000/manage/pages

## Creating a New Page

1. Click **"Create New Page"** button
2. Fill in the form:
   - **Title**: Display name of your page (e.g., "About Us")
   - **Slug**: URL-friendly identifier (e.g., "about-us")
     - Auto-generates from title
     - Use lowercase letters, numbers, and hyphens only
   - **Content**: Use the rich text editor to format your content
   - **Active**: Check to make the page visible to the public
3. Click **"Create Page"**

### Example Pages

- `about-us` → http://localhost:8000/about-us
- `faq` → http://localhost:8000/faq
- `shipping-policy` → http://localhost:8000/shipping-policy
- `refund-policy` → http://localhost:8000/refund-policy

## Editing Pages

1. Go to **Pages** in the sidebar
2. Click the **Edit** button (pencil icon) next to any page
3. Modify the content using the rich text editor
4. Click **"Save Changes"**

### System Pages

Two pages are pre-installed and can be edited:
- **Privacy Policy** (`privacy-policy`)
- **Terms & Conditions** (`terms-conditions`)

These system pages cannot be deleted but can be fully customized.

## Deleting Pages

1. Go to **Pages** in the sidebar
2. Click the **Delete** button (trash icon) next to any custom page
3. Confirm deletion

**Note**: System pages (Privacy Policy, Terms & Conditions) cannot be deleted.

## Accessing Public Pages

All active pages are accessible at:
```
http://localhost:8000/{slug}
```

Examples:
- http://localhost:8000/privacy-policy
- http://localhost:8000/terms-conditions
- http://localhost:8000/about-us
- http://localhost:8000/contact-us

## Rich Text Editor Features

The Quill editor provides:

- **Text Formatting**: Bold, italic, underline, strikethrough
- **Headers**: H1 through H6
- **Lists**: Ordered and unordered
- **Alignment**: Left, center, right, justify
- **Colors**: Text and background colors
- **Links**: Insert hyperlinks
- **Images**: Embed images
- **Code Blocks**: For code snippets
- **Blockquotes**: For quotes
- **Indentation**: Increase/decrease indent

## Customizing Page Content Model

The `PageContent` model is available in your project at:
```
app/Models/PageContent.php
```

You can extend it with custom methods:

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
}
```

## Adding Custom Routes

To create custom routes for your pages, edit `routes/web.php`:

```php
use App\Models\PageContent;

Route::get('/about-us', function () {
    $page = PageContent::getBySlug('about-us');
    return view('pages.custom', ['page' => $page]);
});
```

## Creating Custom Page Templates

Create a custom template at `resources/views/pages/custom.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $page->title }}</h1>
        <div class="content">
            {!! $page->content !!}
        </div>
    </div>
@endsection
```

## Database Structure

The `page_contents` table has the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-friendly identifier (unique) |
| title | string | Page display title |
| content | text | HTML content |
| is_active | boolean | Visibility status |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last update date |

## API Integration (Optional)

You can expose pages via API by adding to `routes/api.php`:

```php
use App\Models\PageContent;

Route::get('/pages', function () {
    return PageContent::where('is_active', true)->get();
});

Route::get('/pages/{slug}', function ($slug) {
    $page = PageContent::getBySlug($slug);
    return $page ?: response()->json(['error' => 'Page not found'], 404);
});
```

## Permissions

Page management requires admin or super-admin role:
- Only users with `admin` or `super-admin` roles can access `/manage/pages`
- Controlled by the middleware: `role:admin|super-admin`

## Tips

1. **Use descriptive slugs** - They become part of your URL
2. **Preview before publishing** - Set page to inactive to preview
3. **Keep system pages updated** - Regularly review Privacy Policy and Terms
4. **Use headers for structure** - Makes content easier to read
5. **Test links** - Verify all links work correctly
6. **Backup before major edits** - Database backups are recommended

## Troubleshooting

### Pages menu not visible
- Ensure you're logged in as admin or super-admin
- Check `config/easypack.php` - admin panel should be enabled

### Cannot create pages
- Verify `page_contents` table exists (run migrations)
- Check file permissions for uploads

### Rich editor not loading
- Check browser console for JavaScript errors
- Ensure Quill CDN is accessible

### Slug already exists error
- Each slug must be unique
- Try a different slug variation

## Support

For more information, see:
- [Easy Pack Documentation](../easy-pack/README.md)
- [Laravel Documentation](https://laravel.com/docs)
- [Quill Editor Documentation](https://quilljs.com/docs/)
