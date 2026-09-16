@extends('layouts.app.main')

@section('content')
    <div class="content">

        {{-- page header --}}
        <div class="row align-items-center mb-4">
            <div class="col">
                <h3 class="fs-7 mb-1">My Forms</h3>
                <p class="text-body-tertiary mb-0">
                    Create, share and manage your forms in one place.
                </p>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFormModal">
                    <i class="fa-solid fa-plus me-md-2"></i>
                    <span class="d-none d-md-inline">New Form</span>
                </button>
            </div>
        </div>

        {{-- template shelf --}}
        @if(count($templates))
            <div class="mb-5">
                <div class="d-flex align-items-baseline justify-content-between mb-2">
                    <h5 class="fs-9 text-uppercase text-body-tertiary fw-bold mb-0">Start from a template</h5>
                </div>

                <div id="splideCarousel" class="splide" aria-label="Form templates">
                    <div class="splide__track">
                        <ul class="splide__list">
                            @foreach ($templates as $template)
                                <li class="splide__slide">
                                    <a href="@route('forms.template', $template['id'])" class="template-card d-block text-decoration-none">
                                        <div class="border rounded-2 overflow-hidden" style="background: url('{{ $template['preview'] }}') top center / cover no-repeat; height: 150px;"></div>
                                        <div class="pt-2 px-1">
                                            <h6 class="mb-0 text-body-emphasis text-truncate">{{ $template['title'] }}</h6>
                                            @if(!empty($template['category']))
                                                <span class="text-body-tertiary fs-10">{{ $template['category'] }}</span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- forms list --}}
        <div class="card">
            <div class="card-body">
                @if($forms->count() > 0)
                    @include('app.forms.partials.list')
                @else
                    <div class="text-center">
                        @include('components.empty', [
                            'alertTitle' => 'No forms yet',
                            'alertMessage' => 'Create a form or pick a template above to start collecting data'
                        ])
                        <button class="btn btn-primary btn-sm mb-4" data-bs-toggle="modal" data-bs-target="#createFormModal">
                            <i class="fa-solid fa-plus me-2"></i> New Form
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('app.forms.partials.create')
@endsection

@style('/vendor/flatpickr/flatpickr.min.css','src')
@script('/vendor/flatpickr/flatpickr.min.js','src')

@style('/vendor/splide/splide.min.css','src')
@script('/vendor/splide/splide.min.js','src')

@script('app.forms.scripts.index')
