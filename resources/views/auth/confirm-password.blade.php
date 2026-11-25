{{-- password.confirm's page, for a full-page visit to a protected url; on the profile page the same check is a modal --}}
<x-layout-auth :title="__('Confirm your password')">
    <form id="confirm" class="pc-auth-card" method="POST" action="{{ route('auth.confirm-password.store') }}" novalidate>
        @csrf

        <div>
            <h1 class="pc-auth-title">Confirm your password</h1>
            <p class="pc-auth-sub">This is a secure area. Confirm your password to carry on, {{ str(auth()->user()->name)->before(' ') }}.</p>
        </div>

        <div>
            <label class="pc-auth-label" for="password">Password</label>
            <span class="pc-input-group">
                <input id="password" name="password" type="password" class="pc-input pr-16" autocomplete="current-password" required data-pc-pw-input aria-describedby="passwordError" data-auth-msg-missing="Enter your password.">
                <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
            </span>
            <div id="passwordError" class="pc-field-error" hidden></div>
        </div>

        <button type="submit" class="pc-btn pc-btn-primary pc-btn-lg pc-btn-block">Confirm</button>

        <p class="pc-auth-foot"><a href="{{ route('dashboard') }}" class="pc-auth-link">Cancel</a></p>
    </form>

    @push('body')
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const form = document.getElementById("confirm");
                form.password.focus();
                auth.submit(form);
            });
        </script>
    @endpush
</x-layout-auth>
