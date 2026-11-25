{{-- One edit modal for the whole table; app.js fills it from the clicked row's data-pc-edit payload --}}
<x-dialog.modal id="editUser" :title="__('Edit user')" subtitle="" action="#" :submit="__('Save changes')">
    <x-form.input id="editName" name="name" :label="__('Full name')" required autocomplete="off" />
    <x-form.input id="editEmail" name="email" type="email" :label="__('Email')" required autocomplete="off" />
    <x-form.select id="editRole" name="role_id" :label="__('Role')" :options="$roleOptions" :placeholder="__('Choose a role')" required />
</x-dialog.modal>
