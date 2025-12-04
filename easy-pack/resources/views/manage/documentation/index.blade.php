@extends('easypack::layouts.app')

@section('pageTitle', $pageTitle)

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ $pageTitle }}</h1>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
        </ol>
    </nav>

    @if (!env('API_ACTIVE', true))
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h4>API is disabled for this environment.</h4>
                <p class="text-muted">Enable the API to view documentation.</p>
            </div>
        </div>
    @else
        @if (count($apiKeys) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-key me-2"></i>API Keys
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            @foreach ($apiKeys as $apiKey)
                                <tr>
                                    <td class="text-muted">API Key</td>
                                    <td class="text-end">
                                        <code class="bg-light px-2 py-1 rounded">{{ $apiKey }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $apiKey }}')" title="Copy to clipboard">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (empty($paths))
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h4>No Documentation Found</h4>
                    <p class="text-muted">Ensure API documentation files exist in the <code>/public/docs</code> directory.</p>
                    <p class="text-muted small">Run <code>php artisan generate:docs</code> to generate documentation.</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-book me-2"></i>Available Documentation
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Document</th>
                                    <th width="150" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paths as $path)
                                    <tr>
                                        <td class="align-middle">
                                            <div class="fw-semibold">{{ $path['name'] }}</div>
                                            @if (!empty($path['description']))
                                                <small class="text-muted">{{ $path['description'] }}</small>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <a class="btn btn-primary btn-sm" href="{{ $path['file_path'] }}?rev={{ mt_rand(500, 50000) }}" target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show a brief toast or alert
        alert('Copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
@endpush
