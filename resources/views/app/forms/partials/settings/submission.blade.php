{{--
    Submission: a redirect after submitting and a webhook (FormSettingsController@updateSubmission). Once a webhook is
    saved, each field carries its own action as an icon button (FormWebhooksController): Send test beside the URL, New
    secret beside the signing secret; Save changes is the section's only full button. Those icons submit the hidden
    forms after this one (forms can't nest).
--}}
<form method="POST" action="{{ route('forms.settings.submission', $form) }}" onsubmit="submitForm(event)" novalidate>
    <x-section id="submissionSettings" :title="__('Submission')" :description="__('What happens once a respondent submits.')" split>
        <div class="flex max-w-100 flex-col gap-4">
            <x-form.input id="formRedirectUrl" name="redirect_url" type="url" :label="__('Redirect to')" :value="$form->content['navigateToUrl'] ?? null"
                placeholder="https://" autocomplete="off" :hint="__('Empty: show the form\'s thank-you message.')" />
            <div>
                <label class="pc-label" for="formWebhookUrl">{{ __('Webhook URL') }}</label>
                <div class="flex items-center gap-2">
                    <input id="formWebhookUrl" name="webhook_url" type="url" value="{{ $form->webhook_url }}" class="pc-input min-w-0 flex-1" placeholder="https://" autocomplete="off"
                        aria-describedby="formWebhookUrlHint formWebhookUrlError">
                    @if ($form->webhook_url)
                        <button type="submit" form="webhookTestForm" class="pc-btn pc-btn-secondary pc-btn-icon flex-none" aria-label="{{ __('Send test') }}" data-pc-tooltip="{{ __('Send test') }}">
                            <i class="fa-regular fa-paper-plane text-base" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
                <div id="formWebhookUrlHint" class="pc-field-hint">{{ __('Each new response is posted here as JSON. Empty: no webhook.') }}</div>
                <div id="formWebhookUrlError" class="pc-field-error" hidden></div>
            </div>
            @if ($form->webhook_url && $form->webhook_secret)
                <div>
                    <label class="pc-label" for="formWebhookSecret">{{ __('Signing secret') }}</label>
                    <div class="flex items-center gap-2">
                        <span class="pc-input-group min-w-0 flex-1">
                            <input id="formWebhookSecret" type="password" value="{{ $form->webhook_secret }}" class="pc-input pe-16 font-mono" readonly autocomplete="off" data-pc-pw-input
                                aria-describedby="formWebhookSecretHint">
                            <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show secret') }}">{{ __('SHOW') }}</button>
                        </span>
                        <button type="submit" form="webhookSecretForm" class="pc-btn pc-btn-secondary pc-btn-icon flex-none" aria-label="{{ __('New secret') }}" data-pc-tooltip="{{ __('New secret') }}"
                            data-pc-confirm="{{ __('Make a new secret?') }}"
                            data-pc-confirm-text="{{ __('The old one stops working at once: requests signed with the new one will fail your check until you update it.') }}"
                            data-pc-confirm-icon="warning" data-pc-confirm-button="{{ __('New secret') }}">
                            <i class="fa-regular fa-arrows-rotate text-base" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p id="formWebhookSecretHint" class="pc-field-hint">{{ __('Check X-Surveyr-Signature: "sha256=" and the HMAC-SHA256 of the X-Surveyr-Timestamp header, a dot and the body, keyed with this secret.') }}</p>
                </div>
            @endif
            <div><button type="submit" class="pc-btn pc-btn-primary">{{ __('Save changes') }}</button></div>
        </div>
    </x-section>
</form>

@if ($form->webhook_url)
    <form id="webhookTestForm" method="POST" action="{{ route('forms.webhook.test', $form) }}" onsubmit="submitForm(event, webhookAnswered)" hidden></form>
    <form id="webhookSecretForm" method="POST" action="{{ route('forms.webhook.secret', $form) }}" onsubmit="submitForm(event)" hidden></form>
    @push('body')
        <script src="@asset('assets/js/pages/form-webhook.js')" defer></script>
    @endpush
@endif
