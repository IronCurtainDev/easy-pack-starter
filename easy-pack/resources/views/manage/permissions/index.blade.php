@extends('easypack::layouts.app')

@section('title', 'Permissions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Permissions</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                <i class="fas fa-layer-group me-1"></i> Bulk Create
            </button>
            <a href="{{ route('manage.permissions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create Permission
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($permissions->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-key fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Permissions Found</h5>
                <p class="text-muted">Create permissions to control access to different features.</p>
                <a href="{{ route('manage.permissions.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create Permission
                </a>
            </div>
        </div>
    @else
        @foreach($permissions as $category => $categoryPermissions)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-folder me-2"></i>
                        {{ Str::headline($category) }}
                        <span class="badge bg-secondary ms-2">{{ $categoryPermissions->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Guard</th>
                                    <th>Roles Using</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryPermissions as $permission)
                                    <tr>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $permission->name }}</code>
                                        </td>
                                        <td>{{ $permission->guard_name }}</td>
                                        <td>
                                            @foreach($permission->roles->take(3) as $role)
                                                <span class="badge bg-primary">{{ $role->name }}</span>
                                            @endforeach
                                            @if($permission->roles->count() > 3)
                                                <span class="badge bg-secondary">+{{ $permission->roles->count() - 3 }} more</span>
                                            @endif
                                            @if($permission->roles->isEmpty())
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('manage.permissions.edit', $permission) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('manage.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this permission?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<!-- Bulk Create Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('manage.permissions.bulk-create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Create Permissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">This will create 4 standard permissions for a resource:</p>
                    <ul class="text-muted small">
                        <li>view-{resource}</li>
                        <li>create-{resource}</li>
                        <li>edit-{resource}</li>
                        <li>delete-{resource}</li>
                    </ul>
                    <div class="mb-3">
                        <label for="resource" class="form-label">Resource Name</label>
                        <input type="text" class="form-control" id="resource" name="resource" placeholder="e.g., users, products, orders" required>
                        <div class="form-text">Use lowercase plural form (e.g., "users" not "user")</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
