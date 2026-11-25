<x-layout-auth :title="__('Email link')">
    <form id="emailLink" class="pc-auth-card" method="POST" action="{{ route('auth.send-email-link') }}" novalidate>
        @csrf

        <div>
            <h1 class="pc-auth-title">Sign in with an email link</h1>
            <p class="pc-auth-sub">No password needed — we'll email you a one-time link that signs you straight in.</p>
        </div>

        @if (session('error'))
            <div class="pc-alert pc-alert-warning" role="alert">{{ session('error') }}</div>
        @endif

        <div>
            <label class="pc-auth-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="pc-input" value="{{ old('email', request('email')) }}" placeholder="you@company.com" autocomplete="username" required aria-describedby="emailError" data-auth-msg-missing="Enter your email.">
            <div id="emailError" class="pc-field-error" hidden></div>
        </div>

        <button type="submit" id="sendBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">
            Email me a link
        </button>

        <div class="pc-auth-divider">or</div>

        <div class="grid grid-cols-1 gap-2.5">
            <a href="{{ route('auth.login') }}" class="pc-btn pc-btn-secondary h-10" data-auth-carry-email>
                <span>Use my password</span>
            </a>
        </div>
    </form>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("emailLink");
                auth.carryEmail(form);
                form.email.focus();
                auth.submit(form);
            });
        </script>
    @endpush
</x-layout-auth>
