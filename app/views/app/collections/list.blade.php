@extends('layouts.app.main')
@section('content')
    <style>
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

        .sd-container-modern__title{
            display: none !important;
        }

        .sd-root-modern.sd-root-modern--full-container{
            background: transparent !important;
        }

        .sd-root-modern--mobile .sd-body.sd-body--static{
            padding: 0px !important;
        }
    </style>
    <div class="content pb-0">
        <div class="row">
            <div class="col-12 mb-4 position-relative">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h3 class="fs-7 mb-0">{{ $form->title }}</h3>
                    @if($activeFilter)
                        <span class="badge badge-phoenix badge-phoenix-primary d-inline-flex align-items-center gap-2 fs-10">
                            <i class="fa-solid fa-filter"></i> {{ $activeFilter->name }}
                            <a href="@route('forms.submissions', $form->id)" class="text-reset" title="Show all submissions" aria-label="Show all submissions"><i class="fa-solid fa-xmark"></i></a>
                        </span>
                    @endif
                </div>

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
                                <a class="dropdown-item" href="@route('reports.forForm', $form->id)">Form Reports</a>
                            </li>
                            @if($form->canBePurgedBy((int) auth()->id()))
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
                    @if($hasAttachments)
                        <button class="btn btn-sm border" id="exportAttachments" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Download attachments">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                    @endif

                    <a class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Visualize"
                        href="@route('forms.visualize', $form->id){{ isset($_GET['filters']) ? '?filters=' . urlencode($_GET['filters']) : ($activeFilter ? '?view=' . $activeFilter->id : '') }}">
                        <i class="fa-solid fa-chart-simple"></i>
                    </a>
                </div>
                <div class="subs-bulk" id="subsBulkBar" hidden>
                    <span data-bulk-count></span>
                    @if($flags)
                        <button type="button" class="btn btn-sm btn-phoenix-secondary" data-bulk-flag>
                            <i class="fa-solid fa-flag me-1"></i> Flag as&hellip;
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-phoenix-secondary" data-bulk-export>
                        <i class="fa-solid fa-file-excel me-1"></i> Export selected
                    </button>
                    @if($hasAttachments)
                        <button type="button" class="btn btn-sm btn-phoenix-secondary" data-bulk-attachments>
                            <i class="fa-solid fa-paperclip me-1"></i> Attachments
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-link px-2" data-bulk-clear>Clear</button>
                </div>
                <div id="surveyDataTable"></div>
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
            <h5 class="offcanvas-title" id="surveyFilterOffcanvasLabel">Filter submissions</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
        <div class="offcanvas-body">
			<div id="fixedFilterContainer"></div>
			<div id="filterRuleContainer"></div>
		</div>

		<div class="offcanvas-footer p-5 d-flex flex-wrap align-items-center gap-2">
			<button id="addRuleBtn" class="btn btn-outline-primary">Add Filter Rule</button>
			<button id="applyFiltersBtn" class="btn btn-success">Apply</button>

			{{-- keep the current rules as a saved filter of this form --}}
			<div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto" id="saveFilterBar">
				<input type="text" class="form-control form-control-sm" id="saveFilterName" maxlength="120" placeholder="Filter name" style="width: 220px;">
				<button type="button" class="btn btn-phoenix-secondary btn-sm" data-save-filter="new">
					<i class="fa-solid fa-floppy-disk me-1"></i> Save as new filter
				</button>
				@if($activeFilter)
					<button type="button" class="btn btn-phoenix-primary btn-sm" data-save-filter="update">
						Update &ldquo;{{ $activeFilter->name }}&rdquo;
					</button>
				@endif
			</div>
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
@style('app.collections.styles.list')
@script('/vendor/gridjs/gridjs.umd.js', 'src')
@script('/vendor/sheetjs/xlsx.full.min.js', 'src')

@script('/vendor/cselect/select.js', 'src')

@style('/vendor/surveyjs/survey-core.min.css','src')
@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/themes/index.min.js','src')
@script('/vendor/surveyjs/surveyjs-widgets.min.js','src')


@script('app.collections.scripts.utils')
@script('app.collections.scripts.list')
@script('app.shared.scripts.filter-core')
@script('app.collections.scripts.filter')