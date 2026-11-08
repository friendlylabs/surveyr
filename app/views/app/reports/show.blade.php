@extends('layouts.app.main')

@style('/vendor/surveyjs/survey-core.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')
@style('app.reports.styles.report')

@section('content')
    <div class="rp-frame exclusionZone">

        {{-- top bar: same anatomy as the editor's --}}
        <div class="rp-topbar rp-no-print">
            <div class="rp-topbar-title">
                <div class="rp-name-row">
                    <span class="rp-name">{{ $report->title }}</span>
                    @if($isPreview)
                        <span class="rp-status is-preview">Draft preview</span>
                    @endif
                </div>
                @if($report->description)
                    <span class="rp-meta is-text" title="{{ $report->description }}">{{ $report->description }}</span>
                @else
                    <span class="rp-meta">
                        {{ count($reportContent['sections']) }} {{ count($reportContent['sections']) === 1 ? 'section' : 'sections' }}
                        · updated {{ $report->updated_at->diffForHumans() }}
                    </span>
                @endif
            </div>

            <button type="button" class="rp-btn is-icon" onclick="window.print()" title="Print">
                <i class="fa-solid fa-print"></i>
            </button>
            @if($accessRole !== 'viewer')
                @if(!$isPreview)
                    <button type="button" class="rp-btn" onclick="shareReport()">
                        <i class="fa-solid fa-link"></i><span class="rp-btn-label">Share report</span>
                    </button>
                @endif
                <a href="@route('reports.edit', $report->id)" class="rp-btn is-primary">
                    <i class="fa-solid fa-pencil"></i><span class="rp-btn-label">{{ $isPreview ? 'Back to editor' : 'Edit report' }}</span>
                </a>
            @endif
        </div>

        <div class="rp-scroll">
            <div id="reportHost" data-empty-html="{{ view('components.empty', [
                'alertTitle' => $isPreview ? 'Nothing drafted yet' : 'Nothing published yet',
                'alertMessage' => $isPreview ? 'The draft has no sections yet.' : 'This report has not been published. Open the editor to write and publish it.',
            ]) }}"></div>
        </div>
    </div>

    @if($accessRole !== 'viewer')
        @include('app.reports.partials.share')
    @endif
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('/vendor/surveyql/surveyql.min.js','src')

@script('app.collections.scripts.viz-engine')
@script('app.reports.scripts.core')
@script('app.reports.scripts.viz')
@script('app.reports.scripts.query')
@script('app.reports.scripts.render')
@script('app.reports.scripts.view')
