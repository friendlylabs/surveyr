{{-- The second sign-in step for accounts with two-factor on (AuthController@twoFactor): an app code, or a recovery code --}}
<x-layout-auth :title="__('Two-factor authentication')">
    <form id="twoFactor" class="pc-auth-card" method="POST" action="{{ route('auth.two-factor.login') }}" novalidate>
        @csrf

        <span class="pc-auth-badge">
            <i class="fa-regular fa-shield-check text-[22px]" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="pc-auth-title">{{ __('Two-factor authentication') }}</h1>
            <p class="pc-auth-sub" data-mode-text="code">{{ __('Enter the 6-digit code from your authenticator app.') }}</p>
            <p class="pc-auth-sub" data-mode-text="recovery" hidden>{{ __('Enter one of the recovery codes you saved when you turned two-factor on. Each works once.') }}</p>
        </div>

        <div data-mode="code">
            <label class="pc-auth-label" for="code">{{ __('Authentication code') }}</label>
            <input id="code" name="code" type="text" class="pc-input text-center font-pc-mono text-lg tracking-[0.5em]" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" required aria-describedby="codeError" data-auth-msg-missing="{{ __('Enter the code from your app.') }}">
            <div id="codeError" class="pc-field-error" hidden></div>
        </div>

        <div data-mode="recovery" hidden>
            <label class="pc-auth-label" for="recovery_code">{{ __('Recovery code') }}</label>
            <input id="recovery_code" name="recovery_code" type="text" class="pc-input font-pc-mono" autocomplete="off" placeholder="xxxxxxxxxx-xxxxxxxxxx" aria-describedby="recoveryCodeError" data-auth-msg-missing="{{ __('Enter a recovery code.') }}" disabled>
            <div id="recoveryCodeError" class="pc-field-error" hidden></div>
        </div>

        <button type="submit" id="verifyBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">{{ __('Verify') }}</button>

        <p class="pc-auth-foot">
            <button type="button" class="pc-auth-link border-0 bg-transparent p-0" id="switchMode">{{ __('Use a recovery code instead') }}</button>
        </p>
    </form>

    <a href="{{ route('auth.login') }}" class="pc-auth-link inline-flex items-center gap-1.5 text-[13px]">
        <i class="fa-regular fa-arrow-left text-sm rtl:-scale-x-100" aria-hidden="true"></i>{{ __('Back to sign in') }}
    </a>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("twoFactor");
                const toggle = document.getElementById("switchMode");
                let mode = "code";

                // one field at a time; the hidden one is disabled so it neither posts nor counts as required
                function setMode(next) {
                    mode = next;
                    form.querySelectorAll("[data-mode]").forEach((el) => {
                        const on = el.dataset.mode === mode;
                        el.hidden = !on;
                        const input = el.querySelector("input");
                        input.disabled = !on;
                        input.required = on;
                        if (on) input.focus();
                    });
                    form.querySelectorAll("[data-mode-text]").forEach((el) => (el.hidden = el.dataset.modeText !== mode));
                    toggle.textContent = mode === "code" ? pc.t("Use a recovery code instead") : pc.t("Use an authentication code instead");
                }

                toggle.addEventListener("click", () => setMode(mode === "code" ? "recovery" : "code"));
                form.code.focus();

                // a timed-out challenge comes back with a redirect to sign in again
                auth.submit(form, (data) => {
                    if (data.redirect) return (window.location.href = data.redirect);
                    toast.error({ message: data.message });
                });
            });
        </script>
    @endpush
</x-layout-auth>
