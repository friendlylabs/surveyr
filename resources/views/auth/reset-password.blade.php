<x-layout-auth :title="__('Reset password')">
    @if ($expired)
        <section id="expired" class="pc-auth-card items-center text-center" aria-labelledby="expiredTitle">
            <span class="pc-auth-badge bg-pc-warning-bg text-pc-warning-text">
                <i class="fa-regular fa-clock text-[22px]" aria-hidden="true"></i>
            </span>
            <div>
                <h1 id="expiredTitle" class="pc-auth-title">{{ __('This link has expired') }}</h1>
                <p class="pc-auth-sub">{{ __('Reset links work once and only for :minutes minutes. Request a new one to carry on.', ['minutes' => $minutes]) }}</p>
            </div>
            <a href="{{ route('auth.forgot-password', array_filter(['email' => $email])) }}" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">{{ __('Send a new link') }}</a>
            <p class="pc-auth-foot"><a href="{{ route('auth.login') }}" class="pc-auth-link">{{ __('Back to sign in') }}</a></p>
        </section>
    @else
        <form id="reset" class="pc-auth-card" method="POST" action="{{ route('auth.update-password') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <h1 class="pc-auth-title">{{ __('Choose a new password') }}</h1>
                <p class="pc-auth-sub">
                    @if ($email)
                        {!! __('For :email.', ['email' => '<strong class="font-semibold text-pc-text">'.e($email).'</strong>']) !!}
                    @endif
                    {{ __('You\'ll be signed out on your other devices.') }}
                </p>
            </div>

            <div class="flex flex-col gap-4">
                <div data-pc-pw-strength>
                    <label class="pc-auth-label" for="password">{{ __('New password') }}</label>
                    <span class="pc-input-group">
                        <input id="password" name="password" type="password" class="pc-input pe-16" autocomplete="new-password" minlength="8" required data-pc-pw-input aria-describedby="passwordHint passwordError" data-auth-msg-missing="{{ __('Choose a password.') }}" data-auth-msg-short="{{ __('Use at least 8 characters.') }}">
                        <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show password') }}">{{ __('SHOW') }}</button>
                    </span>
                    <div class="pc-strength" aria-hidden="true"><span data-pc-strength-bar></span><span data-pc-strength-bar></span><span data-pc-strength-bar></span></div>
                    <div id="passwordHint" class="pc-field-hint">{{ __('At least 8 characters. 12 or more is strong.') }}</div>
                    <div id="passwordError" class="pc-field-error" hidden></div>
                </div>
                <div>
                    <label class="pc-auth-label" for="password_confirmation">{{ __('Confirm password') }}</label>
                    <span class="pc-input-group">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="pc-input pe-16" autocomplete="new-password" required data-pc-pw-input data-auth-match="#password" aria-describedby="confirmError" data-auth-msg-missing="{{ __('Type the password again.') }}" data-auth-msg-match="{{ __('Passwords don\'t match.') }}">
                        <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show password') }}">{{ __('SHOW') }}</button>
                    </span>
                    <div id="confirmError" class="pc-field-error" hidden></div>
                </div>
            </div>

            <button type="submit" id="saveBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">{{ __('Update password') }}</button>

            <p class="pc-auth-foot"><a href="{{ route('auth.login') }}" class="pc-auth-link">{{ __('Cancel') }}</a></p>
        </form>
    @endif

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("reset");
                if (!form) return; // expired state
                form.password.focus();
                // a token that expired or was used while the page was open: reload into the expired state
                auth.submit(form, (data) => (data.expired ? location.reload() : toast.error({ message: data.message })));
            });
        </script>
    @endpush
</x-layout-auth>
