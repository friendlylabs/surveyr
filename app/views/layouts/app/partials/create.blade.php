{{--
    Global creation modals (form, report, space, zone), opened from the topbar quick actions on every page.
    $quickCreate (forms, users) is set by Controller::renderPage; pages that
    scope to a form ($form) get it preselected as a report source.
--}}
@php
    $quickCreate = $quickCreate ?? ['forms' => collect(), 'users' => collect()];
@endphp

@include('app.forms.partials.create')
@include('app.reports.partials.add', ['forms' => $quickCreate['forms'], 'users' => $quickCreate['users']])
@include('app.spaces.partials.create', ['users' => $quickCreate['users']])
@include('app.zones.partials.create')
