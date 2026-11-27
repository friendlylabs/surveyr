{{-- Daily responses for the last 30 days ($daily, FormOverviewController::daily), as an area chart --}}
<x-section id="formDaily" :title="__('Daily responses')" :description="__('The last 30 days.')">
    @if ($stats['total'])
        <x-chart :label="__('Daily responses for the last 30 days')" :height="288" :options="[
            'chart' => ['type' => 'area'],
            'series' => [['name' => __('Responses'), 'data' => array_column($daily, 'count')]],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.3, 'opacityTo' => 0.02]],
            'xaxis' => [
                'categories' => array_map(fn ($day) => $day['date']->format('j M'), $daily),
                'tickAmount' => 6,
                'labels' => ['rotate' => 0, 'hideOverlappingLabels' => true],
                'tooltip' => ['enabled' => false],
            ],
            'yaxis' => ['min' => 0, 'forceNiceScale' => true, 'decimalsInFloat' => 0, 'labels' => ['minWidth' => 16]],
        ]" />
    @else
        <x-empty icon="chart-line" :title="__('No responses yet')">
            <div class="max-w-xs">{{ __('Share the form and the daily count shows up here.') }}</div>
        </x-empty>
    @endif
</x-section>
