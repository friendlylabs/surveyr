{{-- New space (SpacesController@store); data-pc-reset: app.js empties the form each time the modal opens --}}
<x-dialog.modal id="addSpace" :title="__('New space')" :subtitle="__('You\'ll be its Manager; add people from its page.')" :action="route('spaces.store')" :submit="__('Create space')" data-pc-reset>
    @include('app.spaces.partials.form-fields', ['prefix' => 'addSpace', 'space' => null])
</x-dialog.modal>
