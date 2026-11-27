{{--
    Edit space (SpacesController@update), shared by the spaces list's rows: each fills it and points it at its space
    (app.js [data-pc-edit]). The space's own page edits it in its Settings tab instead (partials/settings).
--}}
<x-dialog.modal id="editSpace" :title="__('Edit space')" subtitle="" action="#" :submit="__('Save')">
    @include('app.spaces.partials.form-fields', ['prefix' => 'editSpace', 'space' => null])
</x-dialog.modal>
