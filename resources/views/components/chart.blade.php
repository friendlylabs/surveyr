{{--
    A chart (ApexCharts, resources/js/charts.js), drawn on the app's look and redrawn with light / dark mode. Pass the
    ApexCharts options; colours, font, grid and the toolbar default to the app's, so usually only the type, series and
    axes are needed:
      <x-chart :label="__('Daily responses')" :height="288" :options="['chart' => ['type' => 'area'], 'series' => [...], 'xaxis' => [...]]" />
    label: what the chart shows, for screen readers. Attributes (class, ...) land on the wrapper. The bundle loads once
    per page, in the head stack.
--}}
@props(['options', 'label', 'height' => 288])

@once
    @push('head')
        @vite('resources/js/charts.js')
    @endpush
@endonce

<div {{ $attributes->class('pc-chart') }} data-pc-chart role="img" aria-label="{{ $label }}">
    <div data-pc-chart-canvas style="min-height: {{ (int) $height }}px"></div>
    {{-- the hex flags keep a "</script>" in a label from closing this tag --}}
    <script type="application/json">{!! json_encode(array_replace_recursive(['chart' => ['height' => (int) $height]], $options), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</div>
