@php($features = Laravel\Fortify\Features::class)

<x-layout-auth :title="__('Sign in')">
    <form id="signIn" class="pc-auth-card" method="POST" action="{{ route('auth.authenticate') }}" novalidate>
        @csrf

        @if ($loggedOut)
            <div>
                <h1 class="pc-auth-title">{{ __('You\'ve been logged out') }}</h1>
                <p class="pc-auth-sub">{{ __('Sign in again to pick up where you left off.') }}</p>
            </div>
        @else
            <div>
                <h1 class="pc-auth-title">{{ __('Welcome back') }}</h1>
                <p class="pc-auth-sub">
                    {{ $features::enabled($features::passkeys()) ? __('Continue with a passkey or enter your details.') : __('Enter your details to sign in.') }}
                </p>
            </div>
        @endif

        @if ($passwordReset)
            <div class="pc-alert pc-alert-success" role="status">{{ __('Password updated. Sign in with your new password.') }}</div>
        @endif

        @if ($features::enabled('email-link') || $features::enabled($features::passkeys()))
            {{-- one equal column per sign-in option; a hidden one (no passkey support) gives up its column --}}
            <div class="grid grid-flow-col auto-cols-fr gap-2.5">
                @if ($features::enabled($features::passkeys()))
                    <button type="button" id="passkeyBtn" class="pc-btn pc-btn-outline pc-btn-lg" aria-label="{{ __('Sign in with a passkey') }}">
                        <i class="fa-regular fa-fingerprint text-lg" aria-hidden="true"></i>
                        <span>{{ __('Passkey') }}</span>
                    </button>
                @endif
                @if ($features::enabled('email-link'))
                    <a href="{{ route('auth.email-link') }}" class="pc-btn pc-btn-outline pc-btn-lg" aria-label="{{ __('Email me a sign-in link') }}" data-auth-carry-email>
                        <i class="fa-regular fa-envelope text-lg" aria-hidden="true"></i>
                        <span>{{ __('Email link') }}</span>
                    </a>
                @endif
            </div>

            <div class="pc-auth-divider">{{ __('or') }}</div>
        @endif

        <div class="flex flex-col gap-5">
            <div>
                <label class="pc-auth-label" for="email">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" class="pc-input" value="{{ old('email', request('email')) }}" placeholder="you@company.com" autocomplete="username" required aria-describedby="emailError" data-auth-msg-missing="{{ __('Enter your email.') }}">
                <div id="emailError" class="pc-field-error" hidden></div>
            </div>
            <div>
                <label class="pc-auth-label" for="password">{{ __('Password') }}</label>
                <span class="pc-input-group">
                    <input id="password" name="password" type="password" class="pc-input pe-16" placeholder="••••••••••" autocomplete="current-password" required data-pc-pw-input aria-describedby="passwordError" data-auth-msg-missing="{{ __('Enter your password.') }}">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="{{ __('Show password') }}">{{ __('SHOW') }}</button>
                </span>
                <div id="passwordError" class="pc-field-error" hidden></div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <label class="pc-check text-[13px] text-pc-text-2"><input type="checkbox" name="remember" value="1" checked> {{ __('Keep me signed in') }}</label>
                <a href="{{ route('auth.forgot-password') }}" class="pc-auth-link-quiet" data-auth-carry-email>{{ __('Forgot password') }}</a>
            </div>
        </div>

        <button type="submit" id="signInBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">
            <span data-label>{{ $loggedOut ? __('Sign in again') : __('Sign in') }}</span>
        </button>

        @if (App\Support\Registration::open())
            <p class="pc-auth-foot">{{ __('Don\'t have an account?') }} <a href="{{ route('auth.register') }}" class="pc-auth-link pc-auth-cta" data-auth-carry-email>{{ __('Create an account') }}</a></p>
        @endif
    </form>

    @push('body')
        @if ($features::enabled($features::passkeys()))
            <script src="@asset('assets/js/pc-webauthn.js')" defer></script>
        @endif
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("signIn");
                auth.carryEmail(form);
                (form.email.value ? form.password : form.email).focus();
                // an account with two-factor on gets { twoFactor, redirect } and moves on to the code page
                auth.submit(form);

                const passkey = document.getElementById("passkeyBtn");
                if (!passkey) return;
                if (!pcWebAuthn.supported()) return (passkey.hidden = true);
                passkey.addEventListener("click", async () => {
                    buttonState(passkey, "loading");
                    try {
                        const data = await pcWebAuthn.login("{{ route('auth.passkeys.options') }}", "{{ route('auth.passkeys.login') }}", form.remember.checked);
                        window.location.href = data.redirect || "{{ route('dashboard') }}";
                    } catch (err) {
                        if (err.name !== "NotAllowedError" && err.name !== "AbortError") toast.error({ message: err.message || pc.t("Passkey sign-in didn't work. Try your password.") });
                    } finally {
                        buttonState(passkey, "reset");
                    }
                });
            });
        </script>
    @endpush
</x-layout-auth>
