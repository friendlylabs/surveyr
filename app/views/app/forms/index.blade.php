@extends('layouts.app.main')

@section('content')
    <div class="content" style="padding-bottom: 0 !important;">
        <div class="row">

            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">Form Templates</h3>
                <p class="text-body-tertiary">
                    Choose from a variety of form templates,
                    <a href="#" class="text-phoenix-primary"></a>
                </p>

                <div class="position-absolute end-5 top-0">

                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFormModal">
                        <i class="fa-solid fa-plus me-2 d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">New Form</span>
                    </button>
                </div>

                <div id="splideCarousel" class="splide">
                    <div class="splide__track">
                        <ul class="splide__list">
                            @foreach ($templates as $template)
                                <li class="splide__slide">
                                    <a href="@route('forms.template', $template['id'])" class="text-primary">
                                        <div class="border border-dark rounded" style="background: url('{{ $template['preview'] }}') top center / cover no-repeat; height: 150px;" class="rounded"></div>
                                        <div class="p-0 pt-3 ps-1">
                                            <h5 class="card-title text-primary">{{ $template['title'] }}</h5>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>         
            </div>
        </div>

        <div class="mt-4 mb-0 mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis pt-3 pb-3 border-y" style="min-height: 400px;">
            <div class="card border-0 p-0">
                <div class="card-body p-0">
                    @if($forms->count() > 0)
                        @include('app.forms.partials.list')
                    @else
                        <div class="text-center">
                            @include('components.empty', [
                                'alertTitle' => 'No forms yet',
                                'alertMessage' => 'Create a form and start collecting data'
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('app.forms.partials.create')

    <script type="importmap">
		{
			"imports": {
				"ckeditor5": "/vendor/ckeditor5/ckeditor5.js",
				"ckeditor5/": "/vendor/ckeditor5/"
			}
		}
	</script>
@endsection

@style('/vendor/flatpickr/flatpickr.min.css','src')
@script('/vendor/flatpickr/flatpickr.min.js','src')

@style('/vendor/splide/splide.min.css','src')
@script('/vendor/splide/splide.min.js','src')

@script('app.forms.scripts.index')