{{--
    Loads one SurveyJS bundle (resources/js/surveyjs/{entry}.js) with the licence key from config/surveyr.php, which
    the bundle registers before rendering. Put it in the page's head stack; page scripts then use window.surveyjs.
      @push('head') <x-surveyjs entry="creator" /> @endpush
    entry: form (respondent view) | creator (form builder). Response charts are in-house (resources/js/analytics.js).
--}}
@props(['entry' => 'form'])

@php
    $entries = ['form', 'creator'];

    if (! in_array($entry, $entries, true)) {
        throw new InvalidArgumentException("Unknown SurveyJS entry [{$entry}]; use one of: ".implode(', ', $entries).'.');
    }
@endphp

@if (filled(config('surveyr.surveyjs.license_key')))
    <meta name="surveyjs-license" content="{{ config('surveyr.surveyjs.license_key') }}">
@endif
@vite("resources/js/surveyjs/{$entry}.js")
