{{-- Password: current, new and confirm (ProfileController@password), a submitForm() form --}}
<section id="password" class="pc-card" aria-labelledby="passwordTitle">
    <h2 id="passwordTitle" class="pc-card-header text-sm">
        Password
        <span class="text-xs font-normal text-pc-muted">Changing it signs out your other devices</span>
    </h2>
    <form method="POST" action="{{ route('profile.password') }}" onsubmit="submitForm(event)" class="pc-card-body flex flex-col gap-4" novalidate>
        @csrf
        <div class="max-w-100">
            <label class="pc-label required" for="currentPassword">Current password</label>
            <span class="pc-input-group">
                <input id="currentPassword" name="current_password" type="password" class="pc-input pr-16" autocomplete="current-password" required data-pc-pw-input aria-describedby="currentPasswordError">
                <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
            </span>
            <div id="currentPasswordError" class="pc-field-error" hidden></div>
        </div>
        <div class="flex max-w-100 flex-col gap-4">
            <div data-pc-pw-strength>
                <label class="pc-label required" for="newPassword">New password</label>
                <span class="pc-input-group">
                    <input id="newPassword" name="password" type="password" class="pc-input pr-16" autocomplete="new-password" required data-pc-pw-input aria-describedby="newPasswordHint newPasswordError">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
                </span>
                <div class="pc-strength" aria-hidden="true"><span data-pc-strength-bar></span><span data-pc-strength-bar></span><span data-pc-strength-bar></span></div>
                <div id="newPasswordHint" class="pc-field-hint">At least 8 characters. 12 or more is strong.</div>
                <div id="newPasswordError" class="pc-field-error" hidden></div>
            </div>
            <div>
                <label class="pc-label required" for="newPasswordConfirmation">Confirm new password</label>
                <span class="pc-input-group">
                    <input id="newPasswordConfirmation" name="password_confirmation" type="password" class="pc-input pr-16" autocomplete="new-password" required data-pc-pw-input aria-describedby="newPasswordConfirmationError">
                    <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
                </span>
                <div id="newPasswordConfirmationError" class="pc-field-error" hidden></div>
            </div>
        </div>
        <div>
            <button type="submit" class="pc-btn pc-btn-primary">Update password</button>
        </div>
    </form>
</section>
