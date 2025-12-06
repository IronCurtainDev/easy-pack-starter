@extends('easypack::layouts.app')

@section('title', 'Edit Page')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Page: {{ $page->title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.pages.index') }}">Pages</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                    <h5 class="mb-0">Page Content</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('manage.pages.update', $page->slug) }}" method="POST" id="pageForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Page Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $page->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="20">{{ old('content', $page->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Page is active (visible to public)
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
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
                    <h5 class="mb-0">Page Info</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Slug</small>
                        <code>{{ $page->slug }}</code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Public URL</small>
                        @if($page->slug === 'privacy-policy')
                            <a href="{{ route('pages.privacy-policy') }}" target="_blank" class="text-break">
                                {{ route('pages.privacy-policy') }} <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                        @elseif($page->slug === 'terms-conditions')
                            <a href="{{ route('pages.terms-conditions') }}" target="_blank" class="text-break">
                                {{ route('pages.terms-conditions') }} <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                        @endif
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Created</small>
                        <span>{{ $page->created_at->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block mb-1">Last Updated</small>
                        <span>{{ $page->updated_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link | code | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false
    });

    // Sync TinyMCE content before form submission
    document.getElementById('pageForm').addEventListener('submit', function(e) {
        tinymce.triggerSave();
    });
</script>
@endpush
