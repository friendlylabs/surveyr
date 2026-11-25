{{-- The app menu. Add a section label, then an <x-sidebar.item> per page; wrap role-only sections in @can --}}
<div class="pc-nav-section-label">Overview</div>
<x-sidebar.item route="dashboard" icon="grid-2" label="Dashboard" />

{{-- Administration: admins only --}}
@can('admin')
    <div class="pc-nav-section-label">Administration</div>
    <div class="pc-nav-divider"></div>
    <x-sidebar.item route="admin.users" icon="user-group" label="Users" />
    {{-- a route that doesn't exist yet opens the "under development" dialog --}}
    <x-sidebar.item route="admin.settings" icon="gear" label="Settings" />
@endcan

<div class="pc-nav-section-label">Reference</div>
<div class="pc-nav-divider"></div>
<x-sidebar.item route="components" icon="puzzle-piece" label="Components" />
