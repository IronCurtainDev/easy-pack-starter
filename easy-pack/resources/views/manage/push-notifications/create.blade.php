@extends('easypack::layouts.app')

@section('title', 'Send Push Notification')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Send Push Notification</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.push-notifications.index') }}">Push Notifications</a></li>
                <li class="breadcrumb-item active">Send</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('manage.push-notifications.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Notification Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Notification title" maxlength="255" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Body <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="3" placeholder="Notification message" maxlength="1000" required>{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">General</option>
                                    @foreach($categories as $key => $cat)
                                        <option value="{{ is_string($key) ? $key : $cat }}" {{ old('category') === (is_string($key) ? $key : $cat) ? 'selected' : '' }}>
                                            {{ is_array($cat) ? ($cat['name'] ?? $key) : $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    @foreach($priorities as $priority)
                                        <option value="{{ $priority }}" {{ old('priority', 'normal') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recipients</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Send To <span class="text-danger">*</span></label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="send_type" id="send_user" value="user" {{ old('send_type', 'user') === 'user' ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_user">Specific User</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="send_type" id="send_topic" value="topic" {{ old('send_type') === 'topic' ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_topic">Topic Subscribers</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_type" id="send_all" value="all" {{ old('send_type') === 'all' ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_all">All Users with Push Tokens</label>
                            </div>
                        </div>

                        <div class="mb-3" id="user_select_wrapper">
                            <label for="user_id" class="form-label">Select User</label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                                <option value="">Choose user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 d-none" id="topic_select_wrapper">
                            <label for="topic" class="form-label">Select Topic</label>
                            <select class="form-select @error('topic') is-invalid @enderror" id="topic" name="topic">
                                <option value="">Choose topic...</option>
                                @foreach($topics as $key => $topicName)
                                    <option value="{{ is_string($key) ? $key : $topicName }}" {{ old('topic') === (is_string($key) ? $key : $topicName) ? 'selected' : '' }}>
                                        {{ is_array($topicName) ? ($topicName['name'] ?? $key) : $topicName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('topic')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary rounded p-2 me-2">
                                    <i class="fas fa-bell text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" id="preview_title">Notification Title</div>
                                    <div class="small text-muted" id="preview_body">Notification body text...</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted small mt-2">This is how the notification will appear on devices.</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Send Notification
                    </button>
                    <a href="{{ route('manage.push-notifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sendTypeRadios = document.querySelectorAll('input[name="send_type"]');
    const userWrapper = document.getElementById('user_select_wrapper');
    const topicWrapper = document.getElementById('topic_select_wrapper');

    function toggleRecipientFields() {
        const selected = document.querySelector('input[name="send_type"]:checked').value;
        userWrapper.classList.toggle('d-none', selected !== 'user');
        topicWrapper.classList.toggle('d-none', selected !== 'topic');
    }

    sendTypeRadios.forEach(radio => radio.addEventListener('change', toggleRecipientFields));
    toggleRecipientFields();

    // Preview
    const titleInput = document.getElementById('title');
    const bodyInput = document.getElementById('body');
    const previewTitle = document.getElementById('preview_title');
    const previewBody = document.getElementById('preview_body');

    titleInput.addEventListener('input', () => previewTitle.textContent = titleInput.value || 'Notification Title');
    bodyInput.addEventListener('input', () => previewBody.textContent = bodyInput.value || 'Notification body text...');
});
</script>
@endsection
