{{-- Adds a tab to Visualize (VisualizationTabsController@store), then opens it --}}
<x-dialog.modal id="addTab" :title="__('Add tab')" :subtitle="__('A tab holds its own charts.')" :action="route('forms.visualize.tabs.store', $form)" :submit="__('Add tab')" data-pc-reset>
    <x-form.input id="addTabName" name="name" :label="__('Name')" maxlength="80" required autocomplete="off" :placeholder="__('Equipment')" />
</x-dialog.modal>
