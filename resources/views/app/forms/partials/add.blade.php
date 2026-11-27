{{-- New form (FormsController@store); data-pc-reset: app.js empties the form each time the modal opens --}}
<x-dialog.modal id="addForm" :title="__('New form')" :action="route('forms.store')" :submit="__('Create form')" data-pc-reset>
    <x-form.input id="addFormTitle" name="title" :label="__('Title')" required autocomplete="off" data-pc-autofocus />
    <x-form.textarea id="addFormDescription" name="description" :label="__('Description')" rows="2" />
    @if ($addableSpaces->isNotEmpty())
        <x-form.select id="addFormSpaces" name="spaces" :label="__('Spaces')" :options="$addableSpaces->all()" multiple :placeholder="__('Personal (no space)')"
            :hint="__('People in these spaces get access through their role there.')" />
    @endif
</x-dialog.modal>
