{{-- Two-factor authentication: off / pending setup / on, switched by pages/profile.js (data-two-factor-*) --}}
@php
    $twoFactorState = match (true) {
        $user->hasTwoFactorEnabled() => 'enabled',
        $user->two_factor_secret !== null => 'pending',
        default => 'disabled',
    };
@endphp
<x-section id="twoFactor" :description="__('A code from your phone as a second step when you sign in.')" split data-two-factor-state="{{ $twoFactorState }}">
    <x-slot:title>
        {{ __('Two-factor authentication') }}
        <span data-two-factor-badge="enabled" class="pc-badge pc-badge-success" @if ($twoFactorState !== 'enabled') hidden @endif>{{ __('On') }}</span>
        <span data-two-factor-badge="off" class="pc-badge pc-badge-neutral" @if ($twoFactorState === 'enabled') hidden @endif>{{ __('Off') }}</span>
    </x-slot:title>
    <div>
        {{-- off --}}
        <div data-two-factor-view="disabled" class="flex flex-wrap items-center gap-4" @if ($twoFactorState !== 'disabled') hidden @endif>
            <span class="pc-empty-icon mb-0 flex-none"><i class="fa-duotone fa-shield-check text-[22px]" aria-hidden="true"></i></span>
            <p class="min-w-0 flex-1 text-[13px] text-pc-text-2">{{ __('Add a second step to signing in: a 6-digit code from an authenticator app on your phone (Google Authenticator, 1Password, Authy…).') }}</p>
            <button type="button" class="pc-btn pc-btn-primary" data-two-factor-action="enable">{{ __('Enable') }}</button>
        </div>

        {{-- set up, waiting for the first code --}}
        <div data-two-factor-view="pending" class="flex flex-col gap-4" @if ($twoFactorState !== 'pending') hidden @endif>
            <p class="text-[13px] text-pc-text-2">{{ __('Scan the QR code with your authenticator app, or enter the setup key by hand, then type the code it shows to finish.') }}</p>
            <div class="flex flex-wrap items-start gap-5">
                <div id="twoFactorQr" class="grid size-44 flex-none place-items-center rounded-pc-card border border-pc-border-soft bg-white p-2 [&_svg]:size-full">
                    <span class="pc-spinner text-pc-muted"></span>
                </div>
                <form id="twoFactorConfirmForm" class="flex min-w-60 flex-1 flex-col gap-3" novalidate>
                    <div>
                        <span class="pc-label">{{ __('Setup key') }}</span>
                        <div class="flex items-center gap-2">
                            <code id="twoFactorSecret" class="min-w-0 flex-1 rounded-pc-control bg-pc-surface-alt px-3 py-2 font-pc-mono text-xs break-all text-pc-text-2">…</code>
                            <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" aria-label="{{ __('Copy setup key') }}" data-pc-tooltip="{{ __('Copy') }}" onclick="copyToClipboard(document.getElementById('twoFactorSecret'))"><i class="fa-regular fa-copy text-base" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <x-form.input id="twoFactorCode" name="code" :label="__('Code from the app')" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" maxlength="6" class="pc-input-mono max-w-40 tracking-[0.3em]" required />
                    <div class="flex gap-2">
                        <button type="submit" class="pc-btn pc-btn-primary">{{ __('Confirm and turn on') }}</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-two-factor-action="cancel">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- on --}}
        <div data-two-factor-view="enabled" class="flex flex-col gap-4" @if ($twoFactorState !== 'enabled') hidden @endif>
            <div class="pc-alert pc-alert-success flex items-center gap-3" role="status">
                <i class="fa-regular fa-shield-check pc-alert-icon text-base" aria-hidden="true"></i>
                <span>{{ __('Two-factor authentication is on. You\'ll be asked for a code each time you sign in with your password.') }}</span>
            </div>
            <div>
                <div class="text-[13px] font-semibold">{{ __('Recovery codes') }}</div>
                <p class="text-xs text-pc-muted">{{ __('Each one signs you in once if you lose your phone. Keep them somewhere safe, like a password manager.') }}</p>
                <div id="twoFactorCodes" class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1.5 rounded-pc-card bg-pc-surface-alt px-4 py-3 font-pc-mono text-xs text-pc-text-2 max-sm:grid-cols-1" hidden></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-two-factor-action="codes"><i class="fa-regular fa-eye" aria-hidden="true"></i>{{ __('Show recovery codes') }}</button>
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-two-factor-action="copy-codes" hidden><i class="fa-regular fa-copy" aria-hidden="true"></i>{{ __('Copy') }}</button>
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-two-factor-action="regenerate"><i class="fa-regular fa-arrows-rotate" aria-hidden="true"></i>{{ __('New recovery codes') }}</button>
                <button type="button" class="pc-btn pc-btn-soft-danger pc-btn-sm ms-auto" data-two-factor-action="disable">{{ __('Turn off') }}</button>
            </div>
        </div>
    </div>
</x-section>
