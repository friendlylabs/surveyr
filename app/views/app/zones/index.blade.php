@extends('layouts.app.main')

@section('content')
    <div class="content" style="padding-bottom: 0 !important;">
        <div class="container-fluid position-relative mb-5">
            <h3 class="fs-7">Datazones</h3>
            <p class="text-body-tertiary">
                This is a collection of reusable data that can be loaded into your forms.
            </p>

            <div class="position-absolute end-5 top-0">
                <a class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDatazoneModal">
                    <i class="fa-solid fa-plus me-2 d-inline d-md-none"></i>
                    <span class="d-none d-md-inline">Add Data</span>
                </a>
            </div>
        </div>

        <div class="container-fluid">
            @if($zones->count() > 0)
                @include('app.zones.partials.list')
            @else
                <div class="p-5 mt-5">
                    @include('components.empty', [
                        'alertTitle' => 'No records found',
                        'alertMessage' => 'Start by creating a new datazone'
                    ])
                </div>
            @endif
        </div>
    </div>

    @include('app.zones.partials.add')
@endsection