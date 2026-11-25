{{-- The app menu. Add a section label, then an <x-sidebar.item> per page; wrap role-only sections in @can --}}
<div class="pc-nav-section-label">Overview</div>
<x-sidebar.item route="dashboard" icon="squares-four" label="Dashboard" />

{{-- Administration: admins only --}}
@can('admin')
    <div class="pc-nav-section-label">Administration</div>
    <div class="pc-nav-divider"></div>
    <x-sidebar.item route="admin.users" icon="users" label="Users" />
    {{-- a route that doesn't exist yet opens the "under development" dialog --}}
    <x-sidebar.item route="admin.settings" icon="gear-six" label="Settings" />
@endcan

<div class="pc-nav-section-label">Reference</div>
<div class="pc-nav-divider"></div>
<x-sidebar.item route="components" icon="puzzle-piece" label="Components" />
