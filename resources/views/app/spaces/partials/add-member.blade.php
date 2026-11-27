{{-- Add a workspace member to the space in a role (SpacesController@addMember); emptied each time it opens --}}
<x-dialog.modal id="addMember" :title="__('Add member')" :subtitle="$space->name" :action="route('spaces.members.store', $space)" :submit="__('Add')" data-pc-reset>
    <x-form.select id="addMemberUser" name="user" :label="__('Person')" :options="$candidates->all()" :placeholder="__('Choose someone')" required />
    <x-form.select id="addMemberRole" name="role" :label="__('Role')" :options="$roles" selected="viewer" required />
</x-dialog.modal>
