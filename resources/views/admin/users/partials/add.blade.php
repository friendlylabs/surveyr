{{-- data-pc-reset: app.js empties the form each time the modal opens --}}
<x-dialog.modal id="addUser" :title="__('Add user')" :subtitle="__('They\'ll get an email to choose their own password.')" :action="route('admin.users.store')" :submit="__('Add user')" data-pc-reset>
    <x-form.input id="addName" name="name" :label="__('Full name')" required autocomplete="off" data-pc-autofocus />
    <x-form.input id="addEmail" name="email" type="email" :label="__('Email')" required autocomplete="off" placeholder="name@company.com" />
    <x-form.select id="addRole" name="role" :label="__('Role')" :options="$roleOptions" :placeholder="__('Choose a role')" required />
</x-dialog.modal>
