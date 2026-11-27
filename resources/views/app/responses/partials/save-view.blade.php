{{-- Saves the filter panel's filters as a new view of the form (SubmissionViewsController@store), shared with everyone on it --}}
<x-dialog.modal id="saveView" :title="__('Save as view')" :subtitle="__('Everyone who can see this form can open it.')" :action="route('forms.views.store', $form)" :submit="__('Save view')" data-filter-save-new>
    <input type="hidden" name="filters" value="">
    <x-form.input id="saveViewName" name="name" :label="__('Name')" maxlength="120" required autocomplete="off" :placeholder="__('Flagged for follow-up')" />
    <x-form.input id="saveViewDescription" name="description" :label="__('Description')" maxlength="300" autocomplete="off" :placeholder="__('What this view shows')" />
</x-dialog.modal>
