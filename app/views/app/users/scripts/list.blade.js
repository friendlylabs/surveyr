document.getElementById('editUserModal').addEventListener('show.bs.modal', function (event) {
    user = JSON.parse(event.relatedTarget.getAttribute('data-user'));
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullname').value = user.fullname;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editRole').value = user.role;

    $('#editRole').selectpicker('refresh');
});