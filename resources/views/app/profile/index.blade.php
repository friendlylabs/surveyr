{{--
    The signed-in user's account (ProfileController): profile information and password (forms posted by submitForm),
    then two-factor authentication, browser sessions and passkeys (pages/profile.js). Those last three sit behind
    password.confirm: a 423 opens #confirmPassword, and the action runs again once the password is confirmed.
--}}
<x-layout-app :title="__('Profile')">
    <div id="profilePage" class="flex w-full max-w-3xl flex-col gap-4 pb-6"
        data-confirm-url="{{ route('auth.confirm-password.store') }}"
        @if ($twoFactor)
            data-two-factor-enable="{{ route('profile.two-factor.enable') }}"
            data-two-factor-confirm="{{ route('profile.two-factor.confirm') }}"
            data-two-factor-disable="{{ route('profile.two-factor.disable') }}"
            data-two-factor-qr="{{ route('profile.two-factor.qr-code') }}"
            data-two-factor-secret="{{ route('profile.two-factor.secret-key') }}"
            data-two-factor-codes="{{ route('profile.two-factor.recovery-codes') }}"
            data-two-factor-regenerate="{{ route('profile.two-factor.recovery-codes.regenerate') }}"
        @endif
        @if ($passkeysEnabled)
            data-passkey-options="{{ route('profile.passkeys.options') }}"
            data-passkey-store="{{ route('profile.passkeys.store') }}"
            data-passkey-destroy="{{ route('profile.passkeys.destroy', ['passkey' => '__ID__']) }}"
        @endif
        data-session-destroy="{{ route('profile.sessions.destroy', ['id' => '__ID__']) }}"
        data-session-destroy-others="{{ route('profile.sessions.destroy-others') }}">

        @include('app.profile.partials.general')

        @include('app.profile.partials.password')

        @if ($twoFactor)
            @include('app.profile.partials.tfa')
        @endif

        @include('app.profile.partials.sessions')

        @if ($passkeysEnabled)
            @include('app.profile.partials.passkey')
        @endif
    </div>

    {{-- sensitive actions (two-factor, passkeys, sessions) answer 423 until the password is confirmed; pages/profile.js retries them after this --}}
    <x-dialog.modal id="confirmPassword" title="Confirm your password" subtitle="For your security, confirm it before changing sign-in settings." :action="route('auth.confirm-password.store')" submit="Confirm" size="sm" data-pc-reset>
        <div>
            <label class="pc-label required" for="confirmPasswordInput">Password</label>
            <span class="pc-input-group">
                <input id="confirmPasswordInput" name="password" type="password" class="pc-input pr-16" autocomplete="current-password" required data-pc-pw-input data-pc-autofocus aria-describedby="confirmPasswordInputError">
                <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
            </span>
            <div id="confirmPasswordInputError" class="pc-field-error" hidden></div>
        </div>
    </x-dialog.modal>

    @push('body')
        <script src="{{ asset('assets/js/pc-webauthn.js') }}" defer></script>
        <script src="{{ asset('assets/js/pages/profile.js') }}" defer></script>
    @endpush
</x-layout-app>
