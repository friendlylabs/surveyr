{{-- Sensitive actions (two-factor, passkeys, sessions) answer 423 until the password is confirmed; pages/profile.js opens this and retries them after it --}}
<x-dialog.modal id="confirmPassword" :title="__('Confirm your password')" :subtitle="__('For your security, confirm it before changing sign-in settings.')" :action="route('auth.confirm-password.store')" :submit="__('Confirm')" size="sm" data-pc-reset>
    <div>
        <label class="pc-label required" for="confirmPasswordInput">{{ __('Password') }}</label>
        <span class="pc-input-group">
            <input id="confirmPasswordInput" name="password" type="password" class="pc-input pe-16" autocomplete="current-password" required data-pc-pw-input data-pc-autofocus aria-describedby="confirmPasswordInputError">
            <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show password') }}">{{ __('SHOW') }}</button>
        </span>
        <div id="confirmPasswordInputError" class="pc-field-error" hidden></div>
    </div>
</x-dialog.modal>
