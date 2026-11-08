@extends('layouts.app.main')

@section('content')
    <div class="content">

        <div class="card">
            <div class="card-body">
                @if($zones->count() > 0)
                    @include('app.zones.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No zones yet',
                            'alertMessage' => 'Zones hold reusable data — countries, products, schools — that your forms can load from.'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" data-bs-toggle="modal" data-bs-target="#createZoneModal">
                            <i class="fa-solid fa-plus me-2"></i> New zone
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('app.zones.partials.builder')
@endsection

@style('app.zones.styles.builder')
@script('app.zones.scripts.builder')
