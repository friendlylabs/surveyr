{{--
    Vertical navigation. Phoenix expects this markup shape:
    .navbar-vertical > .navbar-collapse > .navbar-vertical-content > .navbar-nav
    and toggles the html `navbar-vertical-collapsed` class from `.navbar-vertical-toggle`.
--}}
@php
    $isStaff = in_array($loggedUser->role, ['admin', 'moderator']);
    $apiEnabled = SurveyrConfig('api.enabled');

    $navGroups = [
        [
            'label' => 'Overview',
            'items' => [
                ['icon' => 'ph-house',        'label' => 'Dashboard', 'route' => 'app.home',     'active' => 'app.home'],
            ],
        ],
        [
            'label' => 'Workspace',
            'items' => [
                ['icon' => 'ph-rows',         'label' => 'Forms',     'route' => 'forms.list',   'active' => 'app.forms'],
                ['icon' => 'ph-folders',      'label' => 'Spaces',    'route' => 'spaces.list',  'active' => 'app.spaces'],
                ['icon' => 'ph-flag',         'label' => 'Flags',     'route' => 'flags.list',   'active' => 'app.flags'],
            ],
        ],
        [
            'label' => 'Data',
            'items' => [
                ['icon' => 'ph-database',     'label' => 'Zones',     'route' => 'zones.list',   'active' => 'app.zones'],
                ['icon' => 'ph-chart-bar',    'label' => 'Reports',   'route' => 'reports.list', 'active' => 'app.reports'],
            ],
        ],
    ];

    if ($isStaff) {
        $adminItems = [
            ['icon' => 'ph-users', 'label' => 'Users', 'route' => 'users.index', 'active' => 'app.users'],
        ];
        if ($apiEnabled) {
            $adminItems[] = ['icon' => 'ph-plug', 'label' => 'Integrations', 'route' => 'intergration.setup', 'active' => 'app.intergrations'];
        }
        $navGroups[] = ['label' => 'Admin', 'items' => $adminItems];
    }
@endphp

<nav class="navbar navbar-vertical navbar-expand-lg" id="navbarVertical">
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                @foreach ($navGroups as $group)
                    <li class="nav-item">
                        <p class="navbar-vertical-label">{{ $group['label'] }}</p>
                        {!! !$loop->first ? '<hr class="navbar-vertical-line" />' : '' !!}
                        @foreach ($group['items'] as $item)
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ active($item['active']) }}" href="{{ route($item['route']) }}"
                                    data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $item['label'] }}"
                                    data-bs-custom-class="navbar-vertical-tooltip">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><i class="ph-duotone {{ $item['icon'] }}"></i></span>
                                        <span class="nav-link-text-wrapper"><span class="nav-link-text">{{ $item['label'] }}</span></span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="navbar-vertical-footer">
        <a class="nav-link label-1 navbar-vertical-footer-link {{ active('app.profile') }}" href="@route('app.profile')"
            data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Settings"
            data-bs-custom-class="navbar-vertical-tooltip">
            <div class="d-flex align-items-center">
                <span class="nav-link-icon"><i class="ph-duotone ph-gear"></i></span>
                <span class="nav-link-text-wrapper"><span class="nav-link-text">Settings</span></span>
            </div>
        </a>
        <button class="btn navbar-vertical-toggle border-0 w-100 white-space-nowrap d-flex align-items-center" type="button">
            <span class="navbar-vertical-footer-icon"><i class="ph-duotone ph-sidebar-simple"></i></span>
            <span class="navbar-vertical-footer-text">Collapse</span>
        </button>
    </div>
</nav>
