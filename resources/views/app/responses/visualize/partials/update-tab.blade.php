{{-- Renames the open tab (VisualizationTabsController@update) --}}
<x-dialog.modal id="updateTab" :title="__('Rename tab')" :action="route('forms.visualize.tabs.update', [$form, $tab])" :submit="__('Save')">
    <x-form.input id="updateTabName" name="name" :label="__('Name')" maxlength="80" required autocomplete="off" :value="$tab->name" />
</x-dialog.modal>
