@extends('easypack::layouts.app')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Create Permission</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.permissions.index') }}">Permissions</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Permission Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('manage.permissions.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., view-users, create-products" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Use the format: action-resource (e.g., view-users, edit-products)</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create Permission
                            </button>
                            <a href="{{ route('manage.permissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Naming Convention</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Follow this pattern for consistent permission names:</p>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>View</td>
                                <td><code>view-users</code></td>
                            </tr>
                            <tr>
                                <td>Create</td>
                                <td><code>create-users</code></td>
                            </tr>
                            <tr>
                                <td>Edit</td>
                                <td><code>edit-users</code></td>
                            </tr>
                            <tr>
                                <td>Delete</td>
                                <td><code>delete-users</code></td>
                            </tr>
                            <tr>
                                <td>Manage (all)</td>
                                <td><code>manage-users</code></td>
                            </tr>
                        </tbody>
                    </table>
                    @if($categories->isNotEmpty())
                        <hr>
                        <p class="mb-2"><strong>Existing categories:</strong></p>
                        <div>
                            @foreach($categories as $category)
                                <span class="badge bg-secondary me-1">{{ $category }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
