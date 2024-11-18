<!-- edit user modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
            </div>
            <div class="modal-body">
                <form action="{{ route('users.update') }}" method="post" id="editUserForm" onsubmit="submitForm(event)">
                    @csrf
                    <input type="hidden" name="id" id="editUserId">
                    
                    <div class="mb-3">
                        <label for="editFullname" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editFullname" name="fullname" placeholder="John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" placeholder="doe@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select class="form-selector" id="editRole" name="role" data-placeholder="Select User Role" reqired>
                            <option value="user">User</option>
                            <option value="moderator">Moderator</option>
                            @if($loggedUser->role === 'admin')
                                <option value="admin">Admin</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Update User</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>