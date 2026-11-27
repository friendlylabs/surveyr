{{-- Adds a chart to the open tab (VisualizationChartsController@store); opens empty each time --}}
<x-dialog.offcanvas id="addChart" :title="__('Add chart')" :subtitle="__('To :tab', ['tab' => $tab->name])" :action="route('forms.visualize.charts.store', [$form, $tab])" :submit="__('Add chart')" data-viz-chart-form data-viz-chart-new>
    @include('app.responses.visualize.partials.chart-fields', ['prefix' => 'addChart'])
</x-dialog.offcanvas>
