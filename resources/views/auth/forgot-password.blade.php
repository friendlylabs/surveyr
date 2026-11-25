<x-layout-auth :title="__('Forgot password')">
    <form id="forgot" class="pc-auth-card" method="POST" action="{{ route('auth.send-reset-link') }}" novalidate>
        @csrf

        <div>
            <h1 class="pc-auth-title">{{ __('Forgot your password?') }}</h1>
            <p class="pc-auth-sub">{{ __('Enter the email you sign in with and we\'ll send you a link to choose a new one.') }}</p>
        </div>

        <div>
            <input id="email" name="email" type="email" class="pc-input" value="{{ old('email', request('email')) }}" placeholder="you@company.com" autocomplete="username" required aria-describedby="emailError" data-auth-msg-missing="{{ __('Enter your email.') }}">
            <div id="emailError" class="pc-field-error" hidden></div>
        </div>

        <button type="submit" id="sendBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">{{ __('Send reset link') }}</button>

        <p class="pc-auth-foot">
            <a href="{{ route('auth.login') }}" class="pc-auth-link inline-flex items-center gap-1.5" data-auth-carry-email>
                {{ __('Back to sign in') }}
            </a>
        </p>
    </form>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("forgot");
                auth.carryEmail(form);
                form.email.focus();
                auth.submit(form);
            });
        </script>
    @endpush
</x-layout-auth>
