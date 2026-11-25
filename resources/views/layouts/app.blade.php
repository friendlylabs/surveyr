<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @unless($sidebar) data-pc-sidebar-auto="collapsed" @endunless>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session('toast'))<meta name="pc-toast" content="{{ session('toast') }}">@endif
    <title>{{ !empty($title) ? "$title | " . config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/icon.svg') }}">

    <x-theme-boot />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Restore the collapsed sidebar before first paint (see pc-sidebar.js). --}}
    <script>(function(d){try{if(d.dataset.pcSidebarAuto==="collapsed"||matchMedia("(max-width: 1280px)").matches||localStorage.getItem("pc-sidebar")==="collapsed")d.dataset.pcSidebar="collapsed"}catch(e){}})(document.documentElement)</script>

    {{-- Drop inner scrollbar gutters before first paint on mouse devices (see pc-scrollbar.js). --}}
    <script>matchMedia("(hover: hover) and (pointer: fine)").matches&&document.documentElement.classList.add("pc-overlay-scroll")</script>

    @stack('head')
</head>
<body>
<x-page-loader />

<div class="pc-app">
    <div class="pc-topbar">
        <div class="pc-brand">
            <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon -ml-1 lg:hidden" data-pc-trigger="sidebar" aria-label="Open navigation">
                <i class="ph ph-list text-xl" aria-hidden="true"></i>
            </button>
            <a href="{{ url('/') }}" class="pc-logo" aria-label="{{ config('app.name') }} home"><img src="{{ asset('assets/img/icon.svg') }}" alt=""></a>
            <a href="{{ url('/') }}" class="pc-brand-name">{{ config('app.name') }}</a>
        </div>
        <header class="pc-header">
            <h1 class="pc-page-title">{{ $title }}</h1>
            {{ $header ?? '' }}

            @auth
                @php($user = auth()->user())
                <div class="pc-dropdown ml-auto flex-none">
                    <button type="button" class="rounded-full" data-pc-trigger="dropdown" data-pc-target="#userMenu" aria-haspopup="menu" aria-expanded="false" aria-label="Account menu for {{ $user->name }}"><x-avatar :name="$user->name" /></button>
                    <div id="userMenu" class="pc-dropdown-menu pc-dropdown-menu-end w-[260px]" role="menu" aria-hidden="true">
                        <div class="pc-dropdown-header">
                            <x-avatar :name="$user->name" />
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate font-semibold">{{ $user->name }}</span>
                                <span class="truncate text-xs text-pc-muted">{{ $user->role?->label ?? $user->email }}</span>
                            </div>
                        </div>
                        @isset($currentWorkspace)
                            <div class="pc-dropdown-divider"></div>
                            @if (\App\Support\Workspaces::multiple())
                                {{-- SaaS: every workspace the user is in, the current one ticked --}}
                                @foreach ($user->workspaces()->orderBy('name')->get() as $workspace)
                                    <form method="POST" action="{{ route('workspaces.switch', $workspace) }}" onsubmit="submitForm(event)">
                                        <button type="submit" @class(['pc-dropdown-item w-full', 'pc-dropdown-item-active' => $workspace->is($currentWorkspace)]) role="menuitem" @disabled($workspace->is($currentWorkspace))>
                                            <i class="ph ph-buildings text-base" aria-hidden="true"></i><span class="truncate">{{ $workspace->name }}</span>
                                            @if ($workspace->is($currentWorkspace))<i class="ph ph-check ml-auto text-base" aria-label="Current workspace"></i>@endif
                                        </button>
                                    </form>
                                @endforeach
                            @else
                                <div class="pc-dropdown-item pointer-events-none" aria-label="Workspace">
                                    <i class="ph ph-buildings text-base" aria-hidden="true"></i><span class="truncate">{{ $currentWorkspace->name }}</span>
                                </div>
                            @endif
                        @endisset
                        <div class="pc-dropdown-divider"></div>
                        <a href="{{ route('profile') }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => request()->routeIs('profile')]) role="menuitem">
                            <i class="ph ph-user text-base" aria-hidden="true"></i>Profile
                        </a>
                        <div class="pc-dropdown-divider"></div>
                        {{-- light / dark toggle (pc-theme.js) --}}
                        <div class="flex items-center justify-between gap-3 px-3 py-1.5">
                            <span class="text-[13px] text-pc-text-2">Theme</span>
                            <x-theme-picker />
                        </div>
                        <div class="pc-dropdown-divider"></div>
                        <form method="POST" action="{{ route('auth.logout') }}" onsubmit="submitForm(event)">
                            <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem">
                                <i class="ph ph-sign-out text-base" aria-hidden="true"></i>Log out
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </header>
    </div>

    <div class="pc-body">
        <div class="pc-sidebar-backdrop" data-pc-component="sidebar-backdrop"></div>
        <aside class="pc-sidebar" data-pc-component="sidebar" aria-label="Main navigation">
            <div class="pc-sidebar-mobile-head">
                <span class="pc-logo" aria-hidden="true"><img src="{{ asset('assets/img/icon.svg') }}" alt=""></span>
                <span class="text-base font-semibold">{{ config('app.name') }}</span>
                <button type="button" class="pc-close ml-auto" data-pc-trigger="sidebar" aria-label="Close navigation">×</button>
            </div>
            <nav class="pc-nav">
                <x-sidebar.app />
            </nav>
            <div class="pc-nav-footer">
                <button type="button" class="pc-nav-item max-lg:hidden" data-pc-trigger="sidebar-collapse" data-pc-label-expanded="Collapse" data-pc-label-collapsed="Expand sidebar" aria-expanded="true" data-pc-tooltip="Collapse" data-pc-placement="right" data-pc-tooltip-only="collapsed">
                    <span class="pc-nav-icon"><i class="ph ph-sidebar-simple text-xl" aria-hidden="true"></i></span>
                    <span class="pc-nav-label" data-pc-sidebar-label>Collapse</span>
                </button>
            </div>
        </aside>

        <main @class(['pc-main', 'pc-requires-xl' => $desktopOnly])>
            <div id="pcContent" @class(['pc-content' => $contained, 'pc-content-flush' => ! $contained])>
                {{ $slot }}
            </div>

            @if($desktopOnly)
                {{-- Below xl (1280px): frosted over the content, never dismissible. Pages can explain why with <x-slot:screen-gate>. --}}
                <div class="pc-screen-gate" role="alertdialog" aria-modal="true" aria-labelledby="pcScreenGateTitle">
                    <div class="pc-screen-gate-panel">
                        <span class="pc-empty-icon"><i class="ph ph-monitor text-[22px]" aria-hidden="true"></i></span>
                        <div id="pcScreenGateTitle" class="text-base font-semibold text-pc-text">{{ $title ?? 'This page' }} needs a larger screen</div>
                        <p class="max-w-xs">{{ $screenGate ?? 'This page works with several panels side by side.' }} Open it on a laptop or desktop — at least 1280px wide.</p>
                        <a href="{{ url('/') }}" class="pc-btn pc-btn-secondary mt-2">Back to Home</a>
                    </div>
                </div>
                {{-- Keep focus and keyboard out of the content while the gate is up. --}}
                <script>(function(m,c){var s=function(){c.inert=!m.matches};s();m.addEventListener("change",s)})(matchMedia("(min-width: 1280px)"),document.getElementById("pcContent"))</script>
            @endif
        </main>
    </div>
</div>

{{-- pc-core first, app.js last; defer keeps this order. Page-only components (pc-tabs,
     pc-popover, pc-table, pc-toggle) go in @push('body'). --}}
<script src="{{ asset('assets/js/pc-core.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-loader.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-theme.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-toast.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-dialog.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-tooltip.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-dropdown.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-modal.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-offcanvas.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-select.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-date.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-sidebar.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-scrollbar.js') }}" defer></script>
<script src="{{ asset('assets/js/pc-password.js') }}" defer></script>
<script src="{{ asset('assets/js/app.js') }}" defer></script>

@stack('body')
</body>
</html>
