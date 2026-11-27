{{--
    One chart's card: its title and how many answered, the visual switch (icon buttons; only those its kind allows, the
    chart's own first; switching isn't saved), a PNG download, and for editors a menu to edit or remove the chart;
    then the kind's body. $summary comes from App\Support\Analytics, named by the chart's hash.
    Panes: data-viz-pane="chart" holds the chart (drawn by resources/js/visualize.js), data-viz-pane="table" its
    data, shown with the table or heat visual and kept for screen readers otherwise.
--}}
@php
    $name = $summary['name'];
    $chart = $charts->firstWhere('hash', $name);
    $visual = $summary['visuals'][0];
    $charted = in_array($summary['kind'], ['choice', 'choices', 'boolean', 'rating', 'nps', 'number', 'ranking', 'matrix', 'grouped'], true);
    $icons = ['bar' => 'chart-bar', 'line' => 'chart-line', 'donut' => 'chart-pie', 'nps' => 'gauge', 'stacked' => 'chart-simple-horizontal', 'stacked100' => 'percent', 'table' => 'table', 'heat' => 'table-cells'];
    $labels = ['bar' => __('Bars'), 'line' => __('Line'), 'donut' => __('Donut'), 'nps' => __('Net Promoter Score'), 'stacked' => __('Stacked bars'), 'stacked100' => __('100% stacked bars'), 'table' => __('Table'), 'heat' => __('Heat table')];
    // the edit form's fields: a question chart's, or a grouped chart's
    $fields = $chart?->kind === 'grouped'
        ? ['kind' => 'grouped', ...array_intersect_key($chart->rules, array_flip(['label', 'mode', 'series', 'split', 'visual', 'sort', 'limit'])), 'title' => $chart->title ?? '']
        : ['kind' => 'question', 'question' => $chart?->rules['question'] ?? '', 'visual' => $chart?->rules['visual'] ?? '', 'sort' => $chart?->rules['sort'] ?? 'form', 'limit' => $chart?->rules['limit'] ?? 0, 'title' => $chart?->title ?? ''];
    $titleId = 'vizTitle'.$loop->index;
@endphp

<article class="pc-viz-card" data-viz-card="{{ $name }}" aria-labelledby="{{ $titleId }}">
    <header class="pc-viz-head">
        <div class="min-w-0">
            <h2 id="{{ $titleId }}" class="pc-viz-title">{{ $summary['title'] }}</h2>
            @unless ($summary['kind'] === 'missing')
                <p class="pc-viz-meta">
                    {{ __(':answered of :total answered (:percent%)', ['answered' => number_format($summary['answered']), 'total' => number_format($summary['total']), 'percent' => $summary['percent']]) }}
                    @if ($summary['skipped']) · {{ trans_choice(':count skipped|:count skipped', $summary['skipped'], ['count' => number_format($summary['skipped'])]) }}@endif
                    @if ($summary['unreadable']) · {{ trans_choice(':count unreadable|:count unreadable', $summary['unreadable'], ['count' => number_format($summary['unreadable'])]) }}@endif
                </p>
            @endunless
        </div>
        <div class="pc-viz-tools" data-viz-print-hide>
            @if (count($summary['visuals']) > 1)
                {{-- the visual showing, as an icon; the others in its menu --}}
                <div class="pc-dropdown">
                    <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#visualMenu{{ $loop->index }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Show as') }}" data-pc-tooltip="{{ __('Show as') }}">
                        <i class="fa-regular fa-{{ $icons[$visual] }} text-base" aria-hidden="true" data-viz-visual-icon></i>
                    </button>
                    <div id="visualMenu{{ $loop->index }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                        @foreach ($summary['visuals'] as $option)
                            <button type="button" @class(['pc-dropdown-item w-full', 'pc-dropdown-item-active' => $option === $visual]) role="menuitemradio" data-pc-dismiss="dropdown" data-viz-visual="{{ $option }}" data-viz-icon="{{ $icons[$option] }}" aria-checked="{{ $option === $visual ? 'true' : 'false' }}">
                                <i class="fa-regular fa-{{ $icons[$option] }} text-base" aria-hidden="true"></i>{{ $labels[$option] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($charted)
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-viz-png aria-label="{{ __('Download as image') }}" data-pc-tooltip="{{ __('Download as image') }}" @disabled(in_array($visual, ['table', 'heat'], true))>
                    <i class="fa-regular fa-image text-base" aria-hidden="true"></i>
                </button>
            @endif
            @if ($canEdit && $chart)
                <div class="pc-dropdown">
                    <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#chartMenu{{ $loop->index }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Chart options') }}" data-pc-tooltip="{{ __('Chart options') }}">
                        <i class="fa-regular fa-ellipsis text-base" aria-hidden="true"></i>
                    </button>
                    <div id="chartMenu{{ $loop->index }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                        <button type="button" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" data-viz-chart-edit data-viz-payload="{{ json_encode([
                            'action' => route('forms.visualize.charts.update', [$form, $chart]),
                            'fields' => $fields,
                        ]) }}">
                            <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Edit') }}
                        </button>
                        <form method="POST" action="{{ route('forms.visualize.charts.duplicate', [$form, $chart]) }}" onsubmit="submitForm(event)">
                            <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                <i class="fa-regular fa-copy text-base" aria-hidden="true"></i>{{ __('Duplicate') }}
                            </button>
                        </form>
                        <div class="pc-dropdown-divider"></div>
                        <form method="POST" action="{{ route('forms.visualize.charts.destroy', [$form, $chart]) }}" onsubmit="submitForm(event)">
                            <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                data-pc-confirm="{{ __('Remove this chart?') }}" data-pc-confirm-text="{{ __('Only the chart goes; the responses are untouched.') }}"
                                data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Remove') }}">
                                <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Remove') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </header>

    @include('app.responses.visualize.partials.'.match ($summary['kind']) {
        'choice', 'choices', 'boolean' => 'choice',
        'rating', 'nps' => 'rating',
        'number' => 'numbers',
        'ranking' => 'ranking',
        'matrix' => 'matrix',
        'file' => 'files',
        'missing' => 'missing',
        'grouped' => 'grouped',
        default => 'count',
    })
</article>
