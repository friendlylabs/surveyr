@extends('layouts.app.main')

@style('/vendor/surveyjs/defaultV2.min.css','src')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7 mb-5">{{ $form->title }}</h3>

                <div class="position-absolute end-5 top-0">
                    <a href="{{ route('forms.setup', $form->id, $form->slug) }}" class="btn btn-secondary btn-sm me-1">
                        <i class="fa-solid fa-cogs"></i>
                    </a>
                    <a href="{{ route('forms.customize', $form->id, $form->slug) }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">Customize Form</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div id="surveyContainer"></div>
            </div>
        </div>
    
    </div>
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/themes/index.min.js','src')

@script('app.forms.scripts.render')