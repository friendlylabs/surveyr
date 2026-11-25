{{-- data-pc-reset: app.js empties the form each time the modal opens --}}
<x-dialog.modal id="addUser" title="Add user" subtitle="They'll get an email to choose their own password." :action="route('admin.users.store')" submit="Add user" data-pc-reset>
    <x-form.input id="addName" name="name" label="Full name" required autocomplete="off" data-pc-autofocus />
    <x-form.input id="addEmail" name="email" type="email" label="Email" required autocomplete="off" placeholder="name@company.com" />
    <x-form.select id="addRole" name="role_id" label="Role" :options="$roleOptions" placeholder="Choose a role" required />
</x-dialog.modal>
