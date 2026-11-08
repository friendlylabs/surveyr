@extends('layouts.app.main')

@section('content')
    <div class="content fo-page">
        @include('app.forms.partials.overview.header')

        {{-- overview: stats + trend --}}
        <section class="fo-pane" data-pane="overview" {{ $tab === 'overview' ? '' : 'hidden' }}>
            @include('app.forms.partials.overview.stats')
            @include('app.forms.partials.overview.charts')
        </section>

        {{-- submissions: All + saved filters --}}
        <section class="fo-pane" data-pane="submissions" {{ $tab === 'submissions' ? '' : 'hidden' }}>
            @include('app.forms.partials.overview.filters')
        </section>

        {{-- settings: general, access, submission --}}
        <section class="fo-pane" data-pane="settings" {{ $tab === 'settings' ? '' : 'hidden' }}>
            <div class="row g-4">
                <div class="col-lg-3">
                    <nav class="fo-subnav" aria-label="Settings sections">
                        <a href="#generalSettings" class="active"><i class="fa-solid fa-sliders"></i> General</a>
                        <a href="#accessSettings"><i class="fa-solid fa-lock"></i> Access</a>
                        <a href="#submissionSettings"><i class="fa-solid fa-paper-plane"></i> Submission</a>
                    </nav>
                </div>
                <div class="col-lg-9 d-flex flex-column gap-4">
                    @include('app.forms.partials.setup-general')
                    @include('app.forms.partials.setup-access')
                    @include('app.forms.partials.setup-submission')
                </div>
            </div>
        </section>

        {{-- theme --}}
        <section class="fo-pane" data-pane="theme" {{ $tab === 'theme' ? '' : 'hidden' }}>
            @include('app.forms.partials.setup-appearance')
        </section>
    </div>

    @include('app.forms.partials.overview.share')
    @include('app.forms.partials.overview.filter-editor')
@endsection

@style('app.forms.styles.overview')

@script('/vendor/echarts/echarts.min.js', 'src')
@script('/vendor/cselect/select.js', 'src')
@script('/vendor/surveyjs/survey.core.min.js', 'src')

@script('app.shared.scripts.filter-core')
@script('app.collections.scripts.filter')
@script('app.forms.scripts.setup')
