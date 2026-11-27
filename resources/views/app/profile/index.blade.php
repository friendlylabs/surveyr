{{--
    The signed-in user's account (ProfileController): profile information and password (forms posted by submitForm),
    then two-factor authentication, browser sessions and passkeys (pages/profile.js). Those last three sit behind
    password.confirm: a 423 opens #confirmPassword, and the action runs again once the password is confirmed.
--}}
<x-layout-app :title="__('Profile')">
    <div id="profilePage" class="flex w-full max-w-5xl flex-col pb-6"
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

        {{-- sign-in security: one block, no lines between its sections --}}
        <div class="pc-section-group">
            @if ($twoFactor)
                @include('app.profile.partials.tfa')
            @endif

            @include('app.profile.partials.sessions')

            @if ($passkeysEnabled)
                @include('app.profile.partials.passkey')
            @endif
        </div>
    </div>

    @include('app.profile.partials.confirm-password')

    @push('body')
        <script src="@asset('assets/js/pc-webauthn.js')" defer></script>
        <script src="@asset('assets/js/pages/profile.js')" defer></script>
    @endpush
</x-layout-app>
