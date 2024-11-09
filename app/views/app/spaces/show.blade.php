@extends('layouts.app.main')
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">{{ $space->name }}</h3>
                <p class="text-body-tertiary" style="max-width: 250pxs">
                    {{ $space->description }}
                </p>

                @if($space->user_id == auth()->id())
                    <div class="position-absolute end-5 top-0">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editSpaceModal">
                            <i class="fa-solid fa-plus d-inline d-md-none"></i>
                            <span class="d-none d-md-inline">Edit Space</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                @if($forms->count())
                    <div class="card">
                        <div class="card-body">
                            @include('app.forms.partials.list')
                        </div>
                    </div>
                @else
                    <div class="">
                        @include('components.empty', [
                            'title' => 'No forms in this space',
                            'message' => 'Create a form and add it to this space'
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection