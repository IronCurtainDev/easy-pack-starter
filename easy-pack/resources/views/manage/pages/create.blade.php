@extends('easypack::layouts.app')

@section('title', 'Create New Page')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Create New Page</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.pages.index') }}">Pages</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('manage.pages.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Pages
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Page Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('manage.pages.store') }}" method="POST" id="pageForm">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Page Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}" required
                                   placeholder="e.g., About Us">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">The display title of your page</small>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Page Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" value="{{ old('slug') }}" required
                                   placeholder="e.g., about-us" pattern="[a-z0-9\-]+"
                                   title="Only lowercase letters, numbers, and hyphens">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">URL-friendly identifier (lowercase, hyphens only). This will be the URL: /{{ old('slug', 'about-us') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <!-- Quill Editor Container -->
                            <div id="editor" style="min-height: 400px;"></div>
                            <!-- Hidden textarea to store content -->
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      id="content" name="content" style="display: none;" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Use the editor above to format your page content</small>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Page is active (visible to public)
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create Page
                            </button>
                            <a href="{{ route('manage.pages.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tips</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Slug Guidelines:</h6>
                    <ul class="small mb-3">
                        <li>Use lowercase letters only</li>
                        <li>Replace spaces with hyphens (-)</li>
                        <li>No special characters</li>
                        <li>Keep it short and descriptive</li>
                    </ul>

                    <h6 class="fw-bold">Examples:</h6>
                    <ul class="small mb-0">
                        <li><code>about-us</code></li>
                        <li><code>faq</code></li>
                        <li><code>shipping-policy</code></li>
                        <li><code>refund-policy</code></li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Editor Features</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Rich text formatting</li>
                        <li>Headers and lists</li>
                        <li>Links and images</li>
                        <li>Text alignment</li>
                        <li>Code blocks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Include Quill stylesheet -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    #editor {
        height: 400px;
    }
    .ql-editor {
        min-height: 400px;
    }
</style>
@endpush

@push('scripts')
<!-- Include Quill library -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate slug from title
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');

        titleInput.addEventListener('input', function() {
            if (!slugInput.dataset.manualEdit) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });

        slugInput.addEventListener('input', function() {
            this.dataset.manualEdit = 'true';
        });

        // Initialize Quill editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Start writing your page content here...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Load existing content into Quill editor (if any from old input)
        var contentTextarea = document.getElementById('content');
        var existingContent = contentTextarea.value;

        if (existingContent && existingContent.trim() !== '') {
            quill.root.innerHTML = existingContent;
        }

        // Sync Quill content to hidden textarea before form submission
        document.getElementById('pageForm').addEventListener('submit', function(e) {
            var html = quill.root.innerHTML;
            contentTextarea.value = html;
        });
    });
</script>
@endpush
