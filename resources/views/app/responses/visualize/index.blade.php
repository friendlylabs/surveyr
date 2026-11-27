{{--
    Visualize (ResponsesVisualizeController@index): tabs of charts the form's editors build, along the bottom like a
    workbook's sheets; the open tab's charts summarise the responses the responses table lists (the same view, filters,
    search and archived state). A form starts with no tabs. The server counts; pages/response-visualize.js draws the
    charts through the analytics engine (libs/surveyr_analytics) and ApexCharts. Each card keeps a data table (shown
    with the table visual, read by screen readers otherwise). Cards pack into columns (masonry), none wider than a
    printed page.
--}}
<x-layout-app :title="$form->title" :subtitle="__('Visualize').' · '.($activeView ? $activeView->name.' · ' : '').($archived ? trans_choice(':count archived response|:count archived responses', $total) : trans_choice(':count response|:count responses', $total))">
    @push('head')
        @vite('resources/js/analytics.js')
    @endpush

    <x-slot:header>
        <div class="ms-auto flex items-center gap-2" data-viz-print-hide>
            <a href="{{ route('forms.responses', [$form, ...$carried]) }}" class="pc-btn pc-btn-secondary pc-btn-icon" aria-label="{{ __('Back to responses') }}" data-pc-tooltip="{{ __('Back to responses') }}">
                <i class="fa-regular fa-arrow-left rtl:-scale-x-100" aria-hidden="true"></i>
            </a>
            {{-- the saved view is picked in the tab bar (partials/tabs) --}}
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon relative" data-pc-trigger="offcanvas" data-pc-target="#responsesFilter" aria-label="{{ __('Filter') }}" data-pc-tooltip="{{ __('Filter') }}">
                <i class="fa-regular fa-filter text-base" aria-hidden="true"></i>
                @if ($filterCount)<span class="pc-count-badge pc-btn-badge">{{ $filterCount }}</span>@endif
            </button>
            {{-- editors' primary action is adding a chart; then Print is the icon beside it --}}
            @if ($summaries)
                <button type="button" @class(['pc-btn', 'pc-btn-secondary pc-btn-icon' => $canEdit && $tab, 'pc-btn-primary' => ! ($canEdit && $tab)]) data-viz-print aria-label="{{ __('Print') }}" data-pc-tooltip="{{ __('Print') }}">
                    <i class="fa-regular fa-print text-base" aria-hidden="true"></i>@unless ($canEdit && $tab)<span class="pc-btn-label">{{ __('Print') }}</span>@endunless
                </button>
            @endif
            @if ($canEdit && $tab)
                <button type="button" class="pc-btn pc-btn-primary" data-pc-trigger="offcanvas" data-pc-target="#addChart">
                    {{ __('Add chart') }}
                </button>
            @endif
        </div>
    </x-slot:header>

    <div @class(['pc-viz', 'pc-viz-empty' => ! $summaries])>
        {{-- the filters and search in force (the subtitle names the view and count); printed with the date too --}}
        <div class="pc-viz-scope">
            @foreach ($filterLines as $line)
                <span class="pc-badge pc-badge-neutral">{{ $line }}</span>
            @endforeach
            @if ($listing->search)
                <span class="pc-badge pc-badge-neutral">{{ __('Search: “:text”', ['text' => $listing->search]) }}</span>
            @endif
            <span class="pc-viz-printed">{{ $tab?->name }} · {{ __('Printed :date', ['date' => now()->format('j M Y, H:i')]) }}</span>
        </div>

        @foreach ($summaries as $summary)
            @include('app.responses.visualize.partials.card')
        @endforeach

        @if (! $summaries)
            <x-empty icon="chart-column" :title="__('No charts on this tab')">
                <div class="max-w-xs">{{ $canEdit ? __('Use Add chart to pick a question and how it\'s shown.') : __('This form\'s editors haven\'t added charts to this tab yet.') }}</div>
            </x-empty>
        @endif
    </div>

    @include('app.responses.visualize.partials.tabs')

    @include('app.responses.partials.filters', ['returnTo' => 'visualize'])
    @include('app.responses.partials.save-view', ['returnTo' => 'visualize'])
    @if ($canEdit)
        @include('app.responses.visualize.partials.add-tab')
        @if ($tab)
            @include('app.responses.visualize.partials.update-tab')
            @include('app.responses.visualize.partials.add-chart')
            @include('app.responses.visualize.partials.update-chart')
        @endif
    @endif

    {{-- read by pages/response-visualize.js; the hex flags keep a "</script>" in an answer from closing this tag --}}
    <script type="application/json" id="visualizeConfig">{!! json_encode([
        'summaries' => $summaries,
        'answersUrl' => route('forms.responses.visualize.answers', [$form, ...$carried]),
        'questionVisuals' => $questionVisuals ?: (object) [],
        'groupedVisuals' => ['single' => App\Support\Analytics\ChartRules::groupedVisuals('numbers', 1), 'multi' => App\Support\Analytics\ChartRules::groupedVisuals('numbers', 2), 'split' => App\Support\Analytics\ChartRules::groupedVisuals('split', 0)],
        'maxSeries' => App\Support\Analytics\ChartRules::MAX_SERIES,
        'strings' => [
            'responses' => __('Responses'),
            'answered' => __('Answered'),
            'averagePosition' => __('Average position'),
            'detractors' => __('Detractors'),
            'passives' => __('Passives'),
            'promoters' => __('Promoters'),
            'otherValues' => __('Other values'),
            'chart' => ['bar' => __('Bar chart'), 'line' => __('Line chart'), 'donut' => __('Donut chart'), 'nps' => __('Net Promoter Score'), 'stacked' => __('Stacked bar chart'), 'stacked100' => __('100% stacked bar chart')],
            'visuals' => ['' => __('Default'), 'bar' => __('Bars'), 'line' => __('Line'), 'donut' => __('Donut'), 'nps' => __('Net Promoter Score'), 'stacked' => __('Stacked bars'), 'stacked100' => __('100% stacked bars'), 'table' => __('Table'), 'heat' => __('Heat table'), 'files' => __('Files'), 'stats' => __('Figures')],
            'highest' => __('highest: :label, :percent%'),
            'answeredOf' => __(':answered of :total answered'),
            'showing' => __(':from–:to of :total'),
            'failed' => __('The answers couldn\'t be loaded. Try again.'),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    @push('body')
        <script src="@asset('assets/js/pages/response-filters.js')" defer></script>
        <script src="@asset('assets/js/pages/response-visualize.js')" defer></script>
    @endpush
</x-layout-app>
