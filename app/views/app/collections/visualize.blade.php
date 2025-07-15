@extends('layouts.app.main')

@style('/vendor/surveyjs/survey-core.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-12 position-relative">
                <h3 class="fs-7">{{ $form->title }}</h3>
                <p class="text-body-tertiary">
                    View, Visualize and manage your form submissions
                </p>

                <div class="position-absolute end-5 top-0">
                    <a href="@route('forms.submissions', $form->id)" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-table-tree d-md-none"></i>
                        <span class="d-none d-md-inline">Compiled View</span>
                    </a>                    
                </div>
            </div>
        </div>

        <div id="surveyVizPanel" class="py-0"></div>
    </div>

    @if(!_env('SURVEYJS_LICENSE_KEY'))
        <div class="card position-fixed" role="alert" 
            style="bottom: 1rem;right: 0.5rem; max-width: 400px; z-index: 1000; margin-left: 0.5rem;">
            <div class="card-header">
                <strong class="me-auto">License Notice</strong>
            </div>
            <div class="card-body">
                <p class="mb-0">Please purchase a <a href="https://surveyjs.io/licensing" target="_blank">SurveyJS Analytics developer license</a> to use it in your app.</p>
            </div>
        </div>
    @endif
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')

@script('app.collections.scripts.utils')
@script('app.collections.scripts.visualize')