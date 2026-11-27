{{--
    A card's chart pane: an x-chart the page script draws (data-pc-chart-manual), at the height the server set for the
    visual. Hidden when the card shows its table instead.
--}}
@php($chartVisual = in_array($visual, ['table', 'heat'], true) ? $summary['visuals'][0] : $visual)
<div class="pc-viz-chart" data-viz-pane="chart" @if (in_array($visual, ['table', 'heat'], true)) hidden @endif>
    <x-chart :options="[]" :label="$summary['title']" :height="$summary['heights'][$chartVisual] ?? 260" data-pc-chart-manual />
</div>
