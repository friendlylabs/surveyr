@extends('layouts.app.main')
@section('content')
    <style>
        td.gridjs-td {
            padding: 0.5rem 0.75rem;
        }

        .flatpickr-mobile:before  {
            content: attr(placeholder);
            color: gray;
        }
        .flatpickr-mobile:focus[value]:not([value=""]):before {
            display: none;
        }

        input[type="hidden"][value]:not([value=""]) + .flatpickr-mobile:before {
            display: none; 
        }

        table.gridjs-table{
            min-height: calc(100vh - 320px);
        }

        .gridjs-tr{
            position: relative;
        }

        .submission-action {
            position: absolute;
            left: -32px;
            z-index: 1;
            padding-top: 0.14rem;
            background: #6b7280;
            border-radius: 4px;
            transition: left 0.3s ease-in-out;
        }

        .submission-action:hover {
            left: -10px! important;
        }

        .sd-container-modern__title{
            display: none !important;
        }

        /* page specific styles */
        [data-column-id="review"]
        {
            position: sticky !important;
            left: 0;
            z-index: 3;
        }

        .sd-root-modern.sd-root-modern--full-container{
            background: transparent !important;
        }
        
    </style>
    <div class="content pb-0">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <h3 class="fs-7">{{ $form->title }}</h3>

                <div class="position-absolute end-5 top-0">

                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="offcanvas"
                        data-bs-target="#surveyFilterOffcanvas" aria-controls="surveyFilterOffcanvas">
                        <i class="fa-solid fa-filter me-2"></i>
                        <span class="d-none d-md-inline">Filter</span>
                    </button>

                    <!-- dropdown -->
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-light btn-sm border" type="button" id="collectionActions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu mt-1" aria-labelledby="collectionActions">
                            <li>
                                <a class="dropdown-item" href="@route('reports.list', $form->id)">Form Reports</a>
                            </li>
                            @if($form->user_id == auth()->id())
                                <li><a class="dropdown-item text-danger" href="@route('collections.clear', $form->id)"  onclick="confirmDelete(event)" data-delete-msg="Are you sure you want to clear all submissions? This action cannot be undone.">
                                    Purge Submissions
                                </a></li>
                            @endif

                        </ul>
                    </div>

                </div>
            </div>
        </div>

        @if($submissions->count())
            <div class="position-relative">
                <div class="position-absolute" style="right: 0px; top: 0.6rem; z-index:1 ">
                    <button class="btn btn-sm border" id="exportExcel" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Export to Excel">
                        <i class="fa-solid fa-file-excel"></i>
                    </button>
                    
                    <a class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Visualize"
                        href="@route('forms.visualize', $form->id){{ isset($_GET['filters']) ? '?filters=' . $_GET['filters'] : '' }}">
                        <i class="fa-solid fa-chart-simple"></i>
                    </a>
                </div>
                <div style="overflow-x: auto;" id="surveyDataTable"></div>
            </div>
        @else
            <div class="py-5">
                @include('components.empty', [
                    'alertTitle' => 'No records Found',
                    'alertMessage' => 'No submissions have been made to this form yet.'
                ])
            </div>
        @endif
    </div>

    <!-- survey filter offcanvas -->
    <div class="offcanvas offcanvas-bottom w-100" tabindex="-1" id="surveyFilterOffcanvas" aria-labelledby="surveyFilterOffcanvasLabel"
        style="height: 100vh; overflow-y: auto;">
        <div class="offcanvas-header">
				<h5 class="offcanvas-title" id="reportSettingsOffcanvasLabel">Report Settings</h5>
				<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>		<div class="offcanvas-body">
			<div id="fixedFilterContainer"></div>
			<div id="filterRuleContainer"></div>
		</div>

		<div class="offcanvas-footer p-5">
			<button id="addRuleBtn" class="btn btn-outline-primary mt-3">Add Filter Rule</button>
			<button id="applyFiltersBtn" class="btn btn-success mt-3 ms-2">Apply 
		</div>
    </div>

    <!-- submission preview offcanvas -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="surveySubmissionOffcanvas" aria-labelledby="surveySubmissionOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="surveySubmissionOffcanvasLabel">Submission</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="surveySubmissionBlock">
            
        </div>
    </div>
@endsection

@style('/vendor/gridjs/mermaid.min.css', 'src')
@script('/vendor/gridjs/gridjs.umd.js', 'src')
@script('/vendor/sheetjs/xlsx.full.min.js', 'src')

@script('/vendor/cselect/select.js', 'src')

@style('/vendor/surveyjs/survey-core.min.css','src')
@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/themes/index.min.js','src')

@style('/vendor/flatpickr/flatpickr.min.css','src')
@script('/vendor/flatpickr/flatpickr.min.js','src')

@script('app.collections.scripts.utils')
@script('app.collections.scripts.list')
@script('app.collections.scripts.filter')