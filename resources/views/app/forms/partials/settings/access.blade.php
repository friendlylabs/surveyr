{{-- Access: the schedule and an access code (FormSettingsController@updateAccess) --}}
<form method="POST" action="{{ route('forms.settings.access', $form) }}" onsubmit="submitForm(event)" novalidate>
    <x-section id="accessSettings" :title="__('Access')" :description="__('When the form takes responses, and a code respondents enter before they see it.')" split>
        <div class="flex max-w-100 flex-col gap-4">
            <x-form.date id="formOpensAt" name="opens_at" mode="datetime" :label="__('Opens')" :value="$form->opens_at" :hint="__('Empty: open from now.')" />
            <x-form.date id="formClosesAt" name="closes_at" mode="datetime" :label="__('Closes')" :value="$form->closes_at" :hint="__('Empty: no closing date.')" />
            <div>
                <label class="pc-label" for="formAccessCodeInput">{{ __('Access code') }}</label>
                <span class="pc-input-group">
                    <input id="formAccessCodeInput" name="access_code" type="password" value="{{ $form->access_code }}" class="pc-input pe-16" autocomplete="off" data-pc-pw-input
                        placeholder="{{ __('No code: open access') }}" aria-describedby="formAccessCodeInputError">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show code') }}">{{ __('SHOW') }}</button>
                </span>
                <div id="formAccessCodeInputError" class="pc-field-error" hidden></div>
            </div>
            <div><button type="submit" class="pc-btn pc-btn-primary">{{ __('Save changes') }}</button></div>
        </div>
    </x-section>
</form>
