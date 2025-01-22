@extends('layouts.app.main')
@section('content')
    <style>
        [tabulator-field="id"], [tabulator-field="review"] {
            cursor: pointer;
        }
    </style>
    <div class="content pb-0">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">{{ $form->title }}</h3>

                <div class="position-absolute end-5 top-0">

                    <a href="javascript:void(0)" class="btn btn-primary btn-sm" id="purgeFormSubmissions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Clear all Data">
                        <i class="fa-solid fa-trash"></i>
                    </a>

                    <a href="@route('forms.visualize', $form->id)" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-chart d-inline d-md-none"></i>
                        <span class="d-none d-md-inline">Visualize</span>
                    </a>                    
                </div>
            </div>
        </div>


        @if($submissions->count())
            <div class="mt-4 mb-0 mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis pt-3 pb-3 border-y h-100" style="min-height: calc(100vh - 168px);">
                <div style="overflow-x: auto;" id="surveyDataTable">

                </div>
            </div>
        @else
            <div class="py-5">
                @include('components.empty', [
                    'emptyTitle' => 'No records Found',
                    'emptyText' => 'No submissions have been made to this form yet.'
                ])
            </div>
        @endif
    </div>
@endsection

@style('/vendor/tabulator/tabulator.min.css', 'src')
@script('/vendor/tabulator/tabulator.min.js', 'src')

@script('/vendor/sheetjs/xlsx.full.min.js', 'src')

@style('/vendor/surveyjs/defaultV2.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')
@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')

@style('/vendor/surveyjs/plugins/survey.analytics.tabulator.min.css','src')
@script('/vendor/surveyjs/plugins/survey.analytics.tabulator.min.js','src')

@script('app.collections.scripts.list')