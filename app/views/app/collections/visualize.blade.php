@extends('layouts.app.main')

@style('/vendor/surveyjs/defaultV2.min.css','src')
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
                        <i class="fa-solid fa-chart me-2 d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">Compiled View</span>
                    </a>                    
                </div>
            </div>
        </div>

        <div id="surveyVizPanel" class="py-0"></div>
    </div>

    @if(!_env('SURVEYJS_LICENSE_KEY'))
        <div class="toast show position-fixed" role="alert" aria-live="assertive" aria-atomic="true" style="bottom: 1rem;right: 0.5rem;">
            <div class="toast-body">
                <p class="mb-0">Please purchase a <a href="https://surveyjs.io/licensing" target="_blank">SurveyJS Analytics</a> developer license to use it in your app.</p>
            </div>
        </div>
    @endif
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('app.collections.scripts.visualize')