@extends('easypack::layouts.app')

@section('title', 'Invitations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Invitations</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Invitations</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('manage.invitations.create') }}" class="btn btn-primary">
            <i class="fas fa-envelope me-1"></i> Send Invitation
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

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning bg-opacity-10">
                <div class="card-body">
                    <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success bg-opacity-10">
                <div class="card-body">
                    <h3 class="mb-0 text-success">{{ $stats['accepted'] }}</h3>
                    <small class="text-muted">Accepted</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-danger bg-opacity-10">
                <div class="card-body">
                    <h3 class="mb-0 text-danger">{{ $stats['expired'] }}</h3>
                    <small class="text-muted">Expired</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('manage.invitations.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by email...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                @if($stats['expired'] > 0)
                    <div class="col-md-3">
                        <form action="{{ route('manage.invitations.prune') }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Delete all expired invitations?')">
                                <i class="fas fa-broom me-1"></i> Prune Expired
                            </button>
                        </form>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($invitations->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Invitations</h5>
                    <p class="text-muted">Invite users to join your application.</p>
                    <a href="{{ route('manage.invitations.create') }}" class="btn btn-primary">
                        <i class="fas fa-envelope me-1"></i> Send Invitation
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Invited By</th>
                                <th>Status</th>
                                <th>Expires/Accepted</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invitations as $invitation)
                                <tr>
                                    <td>{{ $invitation->email }}</td>
                                    <td>
                                        @if($invitation->role)
                                            <span class="badge bg-primary">{{ $invitation->role }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invitation->inviter)
                                            {{ $invitation->inviter->name }}
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invitation->isAccepted())
                                            <span class="badge bg-success">Accepted</span>
                                        @elseif($invitation->isExpired())
                                            <span class="badge bg-danger">Expired</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invitation->isAccepted())
                                            {{ $invitation->accepted_at->format('M d, Y H:i') }}
                                            @if($invitation->acceptedUser)
                                                <br><small class="text-muted">by {{ $invitation->acceptedUser->name }}</small>
                                            @endif
                                        @else
                                            {{ $invitation->expires_at->format('M d, Y H:i') }}
                                            @if(!$invitation->isExpired())
                                                <br><small class="text-muted">{{ $invitation->expires_at->diffForHumans() }}</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$invitation->isAccepted())
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $invitation->getUrl() }}')" title="Copy Link">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <form action="{{ route('manage.invitations.resend', $invitation) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Resend">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('manage.invitations.destroy', $invitation) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this invitation?')">
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
                    {{ $invitations->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Invitation link copied to clipboard!');
    });
}
</script>
@endsection
