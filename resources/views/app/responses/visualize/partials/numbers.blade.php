{{--
    A number question: mean, median, smallest and largest, and how the answers spread (a histogram, and its table).
--}}
@php($stats = $summary['stats'])
@if ($stats['count'])
    @include('app.responses.visualize.partials.stats', ['figures' => [
        [number_format($stats['mean'], 2), __('Mean')],
        [$stats['median'], __('Median')],
        [$stats['min'], __('Smallest')],
        [$stats['max'], __('Largest')],
    ]])
    @include('app.responses.visualize.partials.chart')
    @include('app.responses.visualize.partials.table', ['heading' => __('Range')])
@else
    <p class="pc-viz-none">{{ __('No answers yet.') }}</p>
@endif
