{{--
    A file question: how many responses sent files and how many files, by type, and the latest files, each opening
    through the app's own link (FormResponsesController@file checks access first).
--}}
@if ($summary['answered'])
    @include('app.responses.visualize.partials.stats', ['figures' => [
        [number_format($summary['answered']), __('Responses with files')],
        [number_format($summary['files']), __('Files')],
    ]])
    @include('app.responses.visualize.partials.table', ['heading' => __('Type'), 'visual' => 'table'])
    <h3 class="pc-viz-subtitle">{{ __('Latest files') }}</h3>
    <ul class="pc-viz-answers">
        @foreach ($summary['latest'] as $file)
            <li class="pc-viz-answer">
                <a class="pc-viz-answer-text" href="{{ route('forms.responses.file', [$form, $file['response'], 'question' => $summary['key'], 'file' => $file['index']]) }}" target="_blank" rel="noopener">
                    <i class="fa-regular fa-paperclip text-pc-muted" aria-hidden="true"></i> {{ $file['name'] }}
                </a>
                <span class="pc-viz-answer-meta"><a href="{{ route('forms.responses', [$form, 'response' => $file['response']]) }}">{{ $file['reference'] }}</a></span>
            </li>
        @endforeach
    </ul>
@else
    <p class="pc-viz-none">{{ __('No files yet.') }}</p>
@endif
