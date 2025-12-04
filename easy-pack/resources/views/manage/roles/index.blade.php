@extends('easypack::layouts.app')

@section('title', 'Roles')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Roles</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('manage.roles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create Role
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($roles->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Roles Found</h5>
                    <p class="text-muted">Create your first role to manage permissions.</p>
                    <a href="{{ route('manage.roles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Role
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Guard</th>
                                <th class="text-center">Users</th>
                                <th class="text-center">Permissions</th>
                                <th>Created</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $role->name === 'super-admin' ? 'danger' : 'primary' }} fs-6">
                                            {{ $role->name }}
                                        </span>
                                    </td>
                                    <td><code>{{ $role->guard_name }}</code></td>
                                    <td class="text-center">
                                        <a href="{{ route('manage.roles.users', $role) }}" class="text-decoration-none">
                                            {{ $role->users_count }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $role->permissions_count }}</td>
                                    <td>{{ $role->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('manage.roles.users', $role) }}" class="btn btn-sm btn-outline-info" title="View Users">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="{{ route('manage.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($role->name !== 'super-admin')
                                            <form action="{{ route('manage.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
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
