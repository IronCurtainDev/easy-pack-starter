@extends('easypack::layouts.app')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Notification Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.push-notifications.index') }}">Push Notifications</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('manage.push-notifications.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Content</h5>
                </div>
                <div class="card-body">
                    <h4>{{ $pushNotification->title }}</h4>
                    <p class="text-muted">{{ $pushNotification->message }}</p>

                    @if($pushNotification->data && count($pushNotification->data) > 0)
                        <hr>
                        <h6>Additional Data</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($pushNotification->data, JSON_PRETTY_PRINT) }}</code></pre>
                    @endif
                </div>
            </div>

            @if($pushNotification->status && $pushNotification->status->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Delivery Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th>Seen</th>
                                        <th>Read</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pushNotification->status as $status)
                                        <tr>
                                            <td>{{ $status->device_type ?? 'Unknown' }}</td>
                                            <td>{{ $status->pivot->seen_at ?? '-' }}</td>
                                            <td>{{ $status->pivot->read_at ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        @if($pushNotification->sent_at)
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                <i class="fas fa-check fa-2x"></i>
                            </div>
                            <h5 class="text-success">Sent</h5>
                            <p class="text-muted mb-0">{{ $pushNotification->sent_at->format('M d, Y H:i:s') }}</p>
                        @else
                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h5 class="text-warning">Pending</h5>
                            <p class="text-muted mb-0">Scheduled: {{ $pushNotification->scheduled_at?->format('M d, Y H:i') ?? 'ASAP' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">UUID</dt>
                        <dd class="col-7"><small>{{ $pushNotification->uuid }}</small></dd>

                        <dt class="col-5">Recipient</dt>
                        <dd class="col-7">
                            @if($pushNotification->notifiable)
                                @if($pushNotification->notifiable instanceof \Oxygen\Starter\Models\User)
                                    {{ $pushNotification->notifiable->name }}
                                @else
                                    Device #{{ $pushNotification->notifiable->id }}
                                @endif
                            @elseif($pushNotification->topic)
                                Topic: {{ $pushNotification->topic }}
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-5">Category</dt>
                        <dd class="col-7">{{ $pushNotification->category ?? '-' }}</dd>

                        <dt class="col-5">Priority</dt>
                        <dd class="col-7">{{ ucfirst($pushNotification->priority ?? 'normal') }}</dd>

                        <dt class="col-5">Silent</dt>
                        <dd class="col-7">{{ $pushNotification->is_silent ? 'Yes' : 'No' }}</dd>

                        <dt class="col-5">Created</dt>
                        <dd class="col-7">{{ $pushNotification->created_at->format('M d, Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if(!$pushNotification->sent_at)
                <form action="{{ route('manage.push-notifications.resend', $pushNotification) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-redo me-1"></i> Resend Notification
                    </button>
                </form>
            @endif

            <form action="{{ route('manage.push-notifications.destroy', $pushNotification) }}" method="POST" onsubmit="return confirm('Delete this notification?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
