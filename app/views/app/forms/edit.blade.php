@extends('layouts.form.main')
@style('/vendor/surveyjs/survey-creator-core.min.css','src')

@section('content')
    
    @if(_env('SURVEYJS_LICENSE_KEY'))
        <style>
            .svc-creator__banner { display: none !important; }
        </style>
    @endif

    <div id="surveyCreator" style="height: 100vh;"></div>

    @if($submissions)
        {{-- Warn before editing a form that already has submissions --}}
        <div class="svc-notice" id="submissionsNotice" role="alertdialog" aria-modal="true" aria-labelledby="submissionsNoticeTitle">
            <div class="svc-notice__dialog">
                <h2 class="svc-notice__title" id="submissionsNoticeTitle">This form has submissions</h2>
                <p class="svc-notice__text">
                    Responses have already been collected with this form. Changing or removing questions
                    may affect existing submissions and could break the form.
                </p>
                <div class="svc-notice__actions">
                    <a class="svc-notice__btn" href="{{ route('forms.list') }}">Go back</a>
                    <button type="button" class="svc-notice__btn svc-notice__btn--danger" id="submissionsNoticeContinue">Continue editing</button>
                </div>
            </div>
        </div>
    @endif

@endsection()

@style('app.forms.styles.creator')
@style('app.forms.styles.copilot')

@script('/vendor/surveyjs/survey-creator-core.min.js','src')
@script('/vendor/surveyjs/survey-creator-js.min.js','src')
@script('/vendor/surveyjs/surveyjs-widgets.min.js','src')

@script('/vendor/ace/ace.min.js','src')
@script('/vendor/ace/ext-language_tools.js','src')
@script('/vendor/ace/ext-searchbox.min.js','src')

@script('app.forms.scripts.copilot')
@script('app.forms.scripts.build')

@if($submissions)
    @push('scripts')
        <script>
            (function () {
                const notice = document.getElementById('submissionsNotice');
                document.getElementById('submissionsNoticeContinue').addEventListener('click', () => notice.remove());
            })();
        </script>
    @endpush
@endif
