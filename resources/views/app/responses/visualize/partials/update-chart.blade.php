{{-- Changes a chart (VisualizationChartsController@update); filled and opened from the card's Edit item (resources/js/visualize.js) --}}
<x-dialog.offcanvas id="updateChart" :title="__('Edit chart')" action="#" :submit="__('Save')" data-viz-chart-form>
    @include('app.responses.visualize.partials.chart-fields', ['prefix' => 'updateChart'])
</x-dialog.offcanvas>
