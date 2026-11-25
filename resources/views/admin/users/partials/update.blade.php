{{-- One edit modal for the whole table; app.js fills it from the clicked row's data-pc-edit payload --}}
<x-dialog.modal id="editUser" title="Edit user" subtitle="" action="#" submit="Save changes">
    <x-form.input id="editName" name="name" label="Full name" required autocomplete="off" />
    <x-form.input id="editEmail" name="email" type="email" label="Email" required autocomplete="off" />
    <x-form.select id="editRole" name="role_id" label="Role" :options="$roleOptions" placeholder="Choose a role" required />
</x-dialog.modal>
