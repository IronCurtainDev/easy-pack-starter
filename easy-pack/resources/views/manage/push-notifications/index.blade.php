@extends('easypack::layouts.app')

@section('title', 'Push Notifications')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Push Notifications</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Push Notifications</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('manage.push-notifications.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Send Notification
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-success bg-opacity-10">
                <div class="card-body">
                    <h3 class="mb-0 text-success">{{ $stats['sent'] }}</h3>
                    <small class="text-muted">Sent</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-warning bg-opacity-10">
                <div class="card-body">
                    <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('manage.push-notifications.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by title or body...">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Notifications</h5>
                    <p class="text-muted">Send your first push notification to users.</p>
                    <a href="{{ route('manage.push-notifications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Send Notification
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Notification</th>
                                <th>Recipient</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ Str::limit($notification->title, 40) }}</div>
                                        <small class="text-muted">{{ Str::limit($notification->message, 50) }}</small>
                                    </td>
                                    <td>
                                        @if($notification->notifiable)
                                            @if($notification->notifiable instanceof \Oxygen\Starter\Models\User)
                                                {{ $notification->notifiable->name }}
                                            @else
                                                Device #{{ $notification->notifiable->id }}
                                            @endif
                                        @elseif($notification->topic)
                                            <span class="badge bg-info">Topic: {{ $notification->topic }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->category)
                                            <span class="badge bg-secondary">{{ $notification->category }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->sent_at)
                                            <span class="badge bg-success">Sent</span>
                                            <br><small class="text-muted">{{ $notification->sent_at->format('M d, H:i') }}</small>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $notification->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('manage.push-notifications.show', $notification) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(!$notification->sent_at)
                                            <form action="{{ route('manage.push-notifications.resend', $notification) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Resend">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('manage.push-notifications.destroy', $notification) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?')">
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

                <div class="mt-3">
                    {{ $notifications->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
