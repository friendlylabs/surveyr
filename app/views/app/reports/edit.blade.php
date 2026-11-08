@extends('layouts.app.main')

@style('/vendor/surveyjs/survey-core.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')
@style('app.reports.styles.report')
@style('app.reports.styles.editor')

@section('content')
    <div id="reportStudio" class="rp-frame rp-studio exclusionZone">

        {{-- top bar --}}
        <div class="rp-topbar">
            <a href="@route('reports.show', $report->id)" class="rp-btn is-icon" title="All reports">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="rp-topbar-title">
                <div class="rp-name-row">
                    <span class="rp-name">{{ $report->title }}</span>
                    <span id="reportStatus" class="rp-status {{ $isPublished ? 'is-published' : '' }}">{{ $isPublished ? 'Published' : 'Draft' }}</span>
                </div>
                <span id="reportMeta" class="rp-meta"></span>
            </div>

            <button type="button" class="rp-btn is-icon rp-edit-only" data-bs-toggle="offcanvas" data-bs-target="#reportFiltersOffcanvas" title="Filters">
                <i class="fa-solid fa-filter"></i>
            </button>
            <button type="button" class="rp-btn is-icon rp-edit-only" data-bs-toggle="offcanvas" data-bs-target="#reportQueriesOffcanvas" title="Queries">
                <i class="fa-solid fa-code"></i>
            </button>
            <button type="button" id="toggleRail" class="rp-btn is-icon rp-edit-only" title="Outline">
                <i class="fa-solid fa-list"></i>
            </button>
            <button type="button" id="toggleInspector" class="rp-btn is-icon rp-edit-only" title="Inspector">
                <i class="fa-solid fa-sliders"></i>
            </button>
            <button type="button" id="togglePreview" class="rp-btn is-icon" title="Preview the draft">
                <i class="fa-solid fa-eye"></i>
            </button>
            <button type="button" id="publishBtn" class="rp-btn is-primary">
                <i class="fa-solid fa-paper-plane"></i><span class="rp-btn-label">Publish</span>
            </button>
        </div>

        <div class="rp-studio-body">
            {{-- outline rail --}}
            <aside class="rp-rail">
                <div class="rp-ins-group">
                    <div class="rp-rail-head">
                        <span class="rp-kicker">Source forms</span>
                        <button type="button" id="addFormBtn" class="rp-rail-btn" title="Add form" aria-label="Add form"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <div id="reportSources" class="rp-sources"></div>
                </div>
                <div class="rp-ins-group" id="reportQueryGroup">
                    <div class="rp-rail-head">
                        <span class="rp-kicker">Queries</span>
                        <button type="button" class="rp-rail-btn" data-q-open title="Edit queries" aria-label="Edit queries"><i class="fa-solid fa-pen"></i></button>
                    </div>
                    <div id="reportQueryList" class="rp-qlist"></div>
                </div>
                <div class="rp-divider"></div>
                <span class="rp-kicker">Outline</span>
                <div id="reportOutline" class="rp-outline"></div>
            </aside>

            {{-- canvas --}}
            <main id="reportCanvas" class="rp-canvas"></main>

            {{-- inspector --}}
            <aside id="reportInspector" class="rp-inspector"></aside>
        </div>

        {{-- preview mode: the draft rendered exactly as the viewer shows it --}}
        <div id="reportPreview" class="rp-scroll" hidden></div>
    </div>

    @include('app.reports.partials.filters')
    @include('app.reports.partials.queries')

    {{-- attach visualisation picker --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="attachPickerOffcanvas" aria-labelledby="attachPickerLabel" style="width: 100%; max-width: 520px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="attachPickerLabel"><i class="fa-solid fa-chart-simple me-2"></i>Attach a visualisation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-3">
            <input type="search" id="attachSearch" class="form-control form-control-sm" placeholder="Search questions across forms…" autocomplete="off">
            <div id="attachList" class="rp-pick-list"></div>
        </div>
    </div>

    {{-- add source forms --}}
    <div class="modal fade" id="addFormModal" tabindex="-1" aria-labelledby="addFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFormLabel">Add source forms</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-3">
                    <input type="search" id="addFormSearch" class="form-control" placeholder="Search forms" autocomplete="off">
                    <div>
                        <span class="rp-kicker" id="addFormHeading">Recently updated</span>
                        <div id="addFormList" class="rp-form-list mt-1"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="small text-body-secondary" id="addFormCount">No forms selected</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="addFormSubmit" disabled>Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.reportEditorConfig = {
            reportId:    {{ (int) $report->id }},
            title:       {!! json_encode($report->title, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            isPublished: {{ $isPublished ? 'true' : 'false' }},
            routes: {
                build: "{{ route('reports.build', $report->id) }}",
                data:  "{{ route('reports.data', $report->id) }}",
                forms: "{{ route('reports.forms', $report->id) }}",
            },
            forms:          {!! json_encode($forms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            availableForms: {!! json_encode($availableForms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            doc:            {!! json_encode($reportDoc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            filters:        {!! json_encode($filters ?: new stdClass, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            svql:           {!! json_encode($svql, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
        };
    </script>
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('/vendor/surveyql/surveyql.min.js','src')

@script('app.collections.scripts.viz-engine')
@script('app.shared.scripts.filter-core')
@script('app.reports.scripts.filters')
@script('app.reports.scripts.core')
@script('app.reports.scripts.viz')
@script('app.reports.scripts.query')
@script('app.reports.scripts.render')
@script('app.reports.scripts.edit')
