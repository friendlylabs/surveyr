<div id="passwordDetails" class="profile-overview-tab d-none">
    <h4 class="card-title">Password</h4>
    <p class="text-muted">Update your account password.</p>
    <hr>

    <form action="{{ route('app.password.update') }}" id="passwordForm" onsubmit="updatePassword(event)">
        @csrf
        <div class="form-group">
            <label for="currentPassword" class="form-label">Current Password</label>
            <input name="current_password" class="form-control" type="password" placeholder="Enter Current Password" required>
        </div>
        <div class="form-group">
            <label for="newPassword" class="form-label">New Password</label>
            <input name="new_password" class="form-control" type="password" placeholder="Enter New Password" required>
        </div>
        <div class="form-group">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input name="confirm_password" class="form-control" type="password" placeholder="Confirm New Password" required>
        </div>
        <div class="form-group text-center pt-3">
            <button class="btn btn-primary btn-form" type="submit">Update Password</button>
        </div>
    </form>
</div>