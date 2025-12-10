<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\PageContent;
use Illuminate\Http\Request;

class PageContentsController extends Controller
{
    /**
     * Display a listing of editable pages.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $pages = PageContent::orderBy('title')->get();

        return view('easypack::manage.pages.index', [
            'pageTitle' => 'Manage Pages',
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('easypack::manage.pages.create', [
            'pageTitle' => 'Create New Page',
        ]);
    }

    /**
     * Store a newly created page.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|alpha_dash|unique:page_contents,slug',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle checkbox value
        $validated['is_active'] = $request->has('is_active') ? true : false;

        PageContent::create($validated);

        return redirect()
            ->route('manage.pages.index')
            ->with('success', 'Page created successfully!');
    }

    /**
     * Show the form for editing a page.
     *
     * @param string $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(string $slug)
    {
        $page = PageContent::where('slug', $slug)->firstOrFail();

        if (empty($page->content)) {
            $page->content = PageContent::getDefaultContent($slug);
        }

        return view('easypack::manage.pages.edit', [
            'pageTitle' => 'Edit Page: ' . $page->title,
            'page' => $page,
        ]);
    }

    /**
     * Update the specified page.
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $slug)
    {
        $page = PageContent::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle checkbox value
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $page->update($validated);

        return redirect()
            ->route('manage.pages.index')
            ->with('success', 'Page updated successfully!');
    }

    /**
     * Remove the specified page.
     *
     * @param string $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $slug)
    {
        $page = PageContent::where('slug', $slug)->firstOrFail();

        // Prevent deletion of system pages
        $systemPages = ['privacy-policy', 'terms-conditions'];
        if (in_array($page->slug, $systemPages)) {
            return redirect()
                ->route('manage.pages.index')
                ->with('error', 'System pages cannot be deleted.');
        }

        $page->delete();

        return redirect()
            ->route('manage.pages.index')
            ->with('success', 'Page deleted successfully!');
    }
}
