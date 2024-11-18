@extends('layouts.app.main')
@section('content')
    <div class="content">

        <div class="row">
            <div class="col-xl-3 col-md-4 col-sm-12 mb-3">
                <div class="card profile-menu-card">
                    <div class="card-body px-0 pb-0">
                        <div class="text-center">
                            <img src="{{ urlPath($loggedUser->avatar) }}" alt="" class="avatar-lg rounded-circle mb-3">
                            <h4 class="mb-0">{{ $loggedUser->fullname }}</h4>
                            <p class="text-muted">{{ $loggedUser->email }}</p>
                        </div>
                        
                        @include('app.user.partials.profile-overview')
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8 col-sm-12">
                <div class="card h-100">
                    <div class="card-body">

                        @include('app.user.partials.profile-details')
                        @include('app.user.partials.password-details')
                        @include('app.user.partials.security-details')

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
@script('app.user.scripts.profile')