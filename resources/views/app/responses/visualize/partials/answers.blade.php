{{--
    A page of typed answers: each one's text, then the response it came in (opens it) and when. The pager asks
    ResponsesVisualizeController@answers for the next page (resources/js/visualize.js builds the same rows).
    $page = {items, page, lastPage, total}; $other: the Other texts of a choice question.
--}}
<div class="pc-viz-answers-wrap" data-viz-answers="{{ $name }}" data-viz-other="{{ $other ? 1 : 0 }}" data-viz-page="{{ $page['page'] }}" data-viz-last="{{ $page['lastPage'] }}" data-viz-total="{{ $page['total'] }}">
    <ol class="pc-viz-answers" data-viz-answers-list>
        @foreach ($page['items'] as $item)
            <li class="pc-viz-answer">
                <p class="pc-viz-answer-text">{{ $item['text'] }}</p>
                <span class="pc-viz-answer-meta"><a href="{{ $item['url'] }}">{{ $item['reference'] }}</a> · {{ $item['date'] }}</span>
            </li>
        @endforeach
    </ol>
    <div class="pc-viz-pager">
        <span class="pc-mono text-xs text-pc-muted" data-viz-range>
            {{ __(':from–:to of :total', ['from' => number_format(($page['page'] - 1) * $page['perPage'] + 1), 'to' => number_format(($page['page'] - 1) * $page['perPage'] + count($page['items'])), 'total' => number_format($page['total'])]) }}
        </span>
        @if ($page['lastPage'] > 1)
            <div class="flex items-center gap-1" data-viz-print-hide>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-viz-step="-1" aria-label="{{ __('Newer answers') }}" @disabled($page['page'] <= 1)>
                    <i class="fa-regular fa-chevron-left rtl:-scale-x-100" aria-hidden="true"></i>
                </button>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-viz-step="1" aria-label="{{ __('Older answers') }}" @disabled($page['page'] >= $page['lastPage'])>
                    <i class="fa-regular fa-chevron-right rtl:-scale-x-100" aria-hidden="true"></i>
                </button>
            </div>
        @endif
    </div>
</div>
