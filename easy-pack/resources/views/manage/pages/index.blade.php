@extends('easypack::layouts.app')

@section('title', 'Manage Pages')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manage Pages</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Pages</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($pages->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Pages Found</h5>
                    <p class="text-muted">Run the seeder to create default pages.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Page Title</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $page)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $page->title }}</div>
                                    </td>
                                    <td>
                                        <code>{{ $page->slug }}</code>
                                    </td>
                                    <td>
                                        @if($page->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $page->updated_at->format('M d, Y h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('manage.pages.edit', $page->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
