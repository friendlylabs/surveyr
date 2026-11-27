{{-- The app menu. Add a section label, then an <x-sidebar.item> per page; wrap role-only sections in @can --}}
<div class="pc-nav-section-label">{{ __('Overview') }}</div>
<x-sidebar.item route="dashboard" icon="grid-2" :label="__('Dashboard')" />

<div class="pc-nav-section-label">{{ __('Workspace') }}</div>
<div class="pc-nav-divider"></div>
<x-sidebar.item route="forms" icon="pen-field" :label="__('Forms')" />
<x-sidebar.item route="spaces" icon="folders" :label="__('Spaces')" />

{{-- Administration: admins only --}}
@can('admin')
    <div class="pc-nav-section-label">{{ __('Administration') }}</div>
    <div class="pc-nav-divider"></div>
    <x-sidebar.item route="admin.users" icon="user-group" :label="__('Users')" />
    <x-sidebar.item route="admin.settings" icon="gear" :label="__('Settings')" />
@endcan

<div class="pc-nav-section-label">{{ __('Reference') }}</div>
<div class="pc-nav-divider"></div>
<x-sidebar.item route="components" icon="puzzle-piece" :label="__('Components')" />
