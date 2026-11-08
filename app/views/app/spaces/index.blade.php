@extends('layouts.app.main')

@section('content')
    <div class="content">

        <div class="card">
            <div class="card-body">
                @if($spaces->count())
                    @include('app.spaces.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No spaces yet',
                            'alertMessage' => 'Group forms into a space and share it with the people who work on them.'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" data-bs-toggle="modal" data-bs-target="#createSpaceModal">
                            <i class="fa-solid fa-plus me-2"></i> New space
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
