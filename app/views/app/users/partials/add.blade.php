<!-- new user modal -->
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserModalLabel">Add New User</h5>
            </div>
            <div class="modal-body">
                <form action="{{ route('users.store') }}" method="post" id="addUserForm" onsubmit="submitForm(event)">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" placeholder="John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="doe@example.com" required>
                    </div>

                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Password</label>
                        <input type="text" class="form-control" id="password" name="password" placeholder="********" required>
                        <i class="fa-solid fa-rotate position-absolute end-2 bottom-3" id='randomPassword' style="cursor: pointer;"
                            onclick="document.getElementById('password').value = Math.random().toString(36).slice(-8)"></i>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-selector" id="role" name="role" data-placeholder="Select User Role" required>
                            <option value="user">User</option>
                            <option value="moderator">Moderator</option>
                            @if($loggedUser->role === 'admin')
                                <option value="admin">Admin</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>