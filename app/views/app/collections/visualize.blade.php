@extends('layouts.app.main')

@style('/vendor/surveyjs/defaultV2.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
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
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('app.collections.scripts.visualize')