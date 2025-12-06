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
}
