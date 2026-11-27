{{-- General: title and description; with manage, the spaces and collaborators (FormSettingsController@updateGeneral) --}}
<form method="POST" action="{{ route('forms.settings.general', $form) }}" onsubmit="submitForm(event)" novalidate>
    <x-section id="generalSettings" :title="__('General')" :description="__('What the form is called, and who works on it.')" split>
        <div class="flex max-w-100 flex-col gap-4">
            <x-form.input id="formTitle" name="title" :label="__('Title')" :value="$form->title" required autocomplete="off" />
            <x-form.textarea id="formDescription" name="description" :label="__('Description')" :value="$form->description" rows="3" />
            @if ($canManage)
                <x-form.select id="formCollaborators" name="collaborators" :label="__('Collaborators')" :options="$collaboratorOptions" multiple
                    :selected="$form->collaborators->pluck('hash')->all()" :placeholder="__('Only the owner')"
                    :hint="$form->owner ? __(':name owns this form. Collaborators can edit it wherever it sits.', ['name' => $form->owner->name]) : null" />
                <x-form.select id="formSpaces" name="spaces" :label="__('Spaces')" :options="$spaceOptions" multiple
                    :selected="$form->spaces->pluck('hash')->all()" :placeholder="__('Personal (no space)')"
                    :hint="__('People in these spaces work on the form through their role there.')" />
            @endif
            <div><button type="submit" class="pc-btn pc-btn-primary">{{ __('Save changes') }}</button></div>
        </div>
    </x-section>
</form>
