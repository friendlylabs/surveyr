<div id="securityDetails" class="profile-overview-tab d-none">
    <h4 class="card-title">Security</h4>
    <p class="text-muted">Update your account security settings.</p>
    <hr>

    <form action="{{ route('app.security.update') }}" method="POST" id="securityForm" onsubmit="submitForm(event)">

        @csrf

        <!-- password -->
        <div class="form-group">
            <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required>
        </div>

        <div class="form-group border-bottom">
            <label class="form-label"> Security Options</label>
        </div>

        <!-- 2FA -->
        <div class="form-group mt-3">
            <label for="2fa" class="form-label">2F Authentication</label>
            <div class="custom-control custom-switch inline-switch">
                <input name="2fa" type="checkbox" class="custom-control-input" id="2fa" {{ $loggedUser->two_fa ? 'checked' : null }}>
                <label class="custom-control-label" for="2fa"></label>
            </div>
        </div>

        <!-- email notifications -->
        <div class="form-group mt-3">
            <label for="emailNotifications" class="form-label">Email Notifications</label>
            <div class="custom-control custom-switch inline-switch">
                <input name="loging" type="checkbox" class="custom-control-input" id="emailNotifications" {{ $loggedUser->notify_signin ? 'checked' : null }}>
                <label class="custom-control-label" for="emailNotifications"></label>
            </div>
        </div>

        <!-- button -->
        <div class="form-group text-center pt-3">
            <button class="btn btn-primary btn-form" id="btnUpdateSecurity" type="submit">Update Security</button>
        </div>
    </form>
</div>