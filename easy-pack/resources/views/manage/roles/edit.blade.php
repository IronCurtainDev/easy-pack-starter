@extends('easypack::layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Edit Role: {{ $role->name }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('manage.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Role Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" {{ $role->name === 'super-admin' ? 'readonly' : '' }} required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($role->name === 'super-admin')
                                <div class="form-text text-warning">The super-admin role name cannot be changed.</div>
                            @endif
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Guard Name</label>
                            <input type="text" class="form-control" value="{{ $role->guard_name }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Users with this role:</span>
                            <strong>{{ $role->users->count() }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total permissions:</span>
                            <strong>{{ $role->permissions->count() }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Permissions</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAll(true)">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Deselect All</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($permissions->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-key fa-2x mb-2"></i>
                                <p>No permissions available.</p>
                            </div>
                        @else
                            @foreach($permissions as $category => $categoryPermissions)
                                <div class="mb-4">
                                    <h6 class="text-uppercase text-muted small mb-2">
                                        {{ Str::headline($category) }}
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="toggleCategory('{{ $category }}')">toggle</button>
                                    </h6>
                                    <div class="row">
                                        @foreach($categoryPermissions as $permission)
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input perm-checkbox category-{{ Str::slug($category) }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Role
            </button>
            <a href="{{ route('manage.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleAll(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
}

function toggleCategory(category) {
    const slug = category.toLowerCase().replace(/\s+/g, '-');
    const checkboxes = document.querySelectorAll('.category-' + slug);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endsection
