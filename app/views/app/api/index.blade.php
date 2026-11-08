@extends('layouts.app.main')

@section('content')
    <div class="content">

        <div class="card">
            <div class="card-body">
                @if($apiKeys->count())
                    @include('app.api.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No API keys yet',
                            'alertMessage' => 'Issue a key to let external services talk to the Surveyr API on your behalf.'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                            <i class="fa-solid fa-key me-2"></i> Issue key
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('app.api.partials.add')
@endsection
