@extends('layouts.app.main')
@section('content')
    <div class="content">

        <div class="container-fluid">
            <h3 class="fs-7">API Keys</h3>
            <p class="text-body-tertiary">
                Manage your API keys for external integrations.
            </p>

            <div class="position-absolute end-5 top-0">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                    <i class="fa-solid fa-plus d-inline d-md-none"></i>
                    <span class="d-none d-md-inline">Issue Key</span>
                </button>
            </div>
        </div>

        <div class="container-fluid mt-5">
            <div class="card d-grid" style="min-height: calc(100vh - 240px)">
                @if($apiKeys->count())
                    <div class="card-body">
                        @include('app.api.partials.list')
                    </div>
                @else
                    <div class="d-flex justify-content-center align-items-center">
                        @include('components.empty', [
                            'title' => 'No API keys yet',
                            'message' => 'Create an API key to start integrating with other services'
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @if(SurveyrConfig('api.enabled') && $loggedUser->role == 'admin')
        @include('app.api.partials.add')
    @endif
@endsection