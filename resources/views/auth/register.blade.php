@php($domains = App\Support\Registration::allowedDomains())

<x-layout-auth :title="__('Create an account')">
    <form id="register" class="pc-auth-card" method="POST" action="{{ route('auth.store') }}" novalidate>
        @csrf

        <div>
            <h1 class="pc-auth-title">Create an account</h1>
            <p class="pc-auth-sub">
                It takes a minute.
                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()))
                    We'll email you a link to confirm your address.
                @endif
            </p>
        </div>

        <div class="flex flex-col gap-4">
            <div>
                <label class="pc-auth-label" for="name">Full name</label>
                <input id="name" name="name" type="text" class="pc-input" value="{{ old('name') }}" placeholder="Jane Doe" autocomplete="name" required aria-describedby="nameError" data-auth-msg-missing="Enter your name.">
                <div id="nameError" class="pc-field-error" hidden></div>
            </div>
            <div>
                <label class="pc-auth-label" for="email">Email</label>
                <input id="email" name="email" type="email" class="pc-input" value="{{ old('email', request('email')) }}" placeholder="you@{{ $domains[0] ?? 'company.com' }}" autocomplete="username" required aria-describedby="{{ $domains ? 'emailHint ' : '' }}emailError" data-auth-msg-missing="Enter your email.">
                @if ($domains)
                    {{-- config/auth.php registration.allow_email_domains --}}
                    <div id="emailHint" class="pc-field-hint">Use your {{ collect($domains)->map(fn ($domain) => "@{$domain}")->join(', ', ' or ') }} address.</div>
                @endif
                <div id="emailError" class="pc-field-error" hidden></div>
            </div>
            <div data-pc-pw-strength>
                <label class="pc-auth-label" for="password">Password</label>
                <span class="pc-input-group">
                    <input id="password" name="password" type="password" class="pc-input pr-16" autocomplete="new-password" minlength="8" required data-pc-pw-input aria-describedby="passwordHint passwordError" data-auth-msg-missing="Choose a password." data-auth-msg-short="Use at least 8 characters.">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
                </span>
                <div class="pc-strength" aria-hidden="true"><span data-pc-strength-bar></span><span data-pc-strength-bar></span><span data-pc-strength-bar></span></div>
                <div id="passwordHint" class="pc-field-hint">At least 8 characters. 12 or more is strong.</div>
                <div id="passwordError" class="pc-field-error" hidden></div>
            </div>
            <div>
                <label class="pc-auth-label" for="password_confirmation">Confirm password</label>
                <span class="pc-input-group">
                    <input id="password_confirmation" name="password_confirmation" type="password" class="pc-input pr-16" autocomplete="new-password" required data-pc-pw-input data-auth-match="#password" aria-describedby="confirmError" data-auth-msg-missing="Type the password again." data-auth-msg-match="Passwords don't match.">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
                </span>
                <div id="confirmError" class="pc-field-error" hidden></div>
            </div>
        </div>

        <button type="submit" id="registerBtn" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">Create account</button>

        <p class="pc-auth-foot">Already have an account? <a href="{{ route('auth.login') }}" class="pc-auth-link pc-auth-cta" data-auth-carry-email>Sign in</a></p>
    </form>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("register");
                auth.carryEmail(form);
                form.name.focus();
                auth.submit(form);
            });
        </script>
    @endpush
</x-layout-auth>
