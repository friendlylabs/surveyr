<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Support\Locales::direction() }}" @unless($sidebar) data-pc-sidebar-auto="collapsed" @endunless>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session('toast'))<meta name="pc-toast" content="{{ session('toast') }}">@endif
    <title>{{ !empty($title) ? "$title | " . config('app.name') : config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">

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

{{--
    The shell: a dark frame holding the sidebar (brand, menu, account) and the content sheet (page title,
    the page's header slot for actions, then the page). The sheet scrolls on its own; the frame and sidebar stay put.
    Below lg the sidebar is a drawer opened from the page head, and the sheet runs edge to edge.
--}}
<div class="pc-app">
    <div class="pc-sidebar-backdrop" data-pc-component="sidebar-backdrop"></div>
    <aside class="pc-sidebar" data-pc-component="sidebar" aria-label="{{ __('Main navigation') }}">
        <div class="pc-sidebar-head">
            <a href="{{ url('/') }}" class="pc-sidebar-brand" aria-label="{{ __(':app home', ['app' => config('app.name')]) }}">
                <img src="{{ asset('assets/img/logo-dark.svg') }}" alt="" class="pc-sidebar-logo">
                <img src="{{ asset('assets/img/favicon.svg') }}" alt="" class="pc-sidebar-mark">
            </a>
            <button type="button" class="pc-sidebar-tool pc-sidebar-collapse max-lg:hidden" data-pc-trigger="sidebar-collapse" data-pc-label-expanded="{{ __('Collapse sidebar') }}" data-pc-label-collapsed="{{ __('Expand sidebar') }}" aria-expanded="true" aria-label="{{ __('Collapse sidebar') }}" data-pc-tooltip="{{ __('Collapse sidebar') }}" data-pc-placement="right">
                <i class="fa-solid fa-angles-left pc-sidebar-collapse-icon rtl:-scale-x-100" aria-hidden="true"></i>
                <i class="fa-solid fa-chevron-right pc-sidebar-expand-icon text-[10px] rtl:-scale-x-100" aria-hidden="true"></i>
            </button>
            <button type="button" class="pc-sidebar-tool lg:hidden" data-pc-trigger="sidebar" aria-label="{{ __('Close navigation') }}">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="pc-nav">
            <x-sidebar.app />
        </nav>

        @auth
            @php($user = auth()->user())
            <div class="pc-sidebar-foot">
                {{-- the account (avatar + name) opens the menu; the theme toggle sits beside it --}}
                <div class="pc-dropdown pc-sidebar-account-row">
                    <button type="button" class="pc-sidebar-account" data-pc-trigger="dropdown" data-pc-target="#userMenu" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Account menu for :name', ['name' => $user->name]) }}">
                        <x-avatar :user="$user" size="sm" />
                        <span class="pc-sidebar-account-text">
                            <span class="truncate font-medium text-pc-sidebar-strong">{{ $user->name }}</span>
                            <span class="truncate text-xs text-pc-sidebar-muted">{{ $user->role?->label ?? $user->email }}</span>
                        </span>
                    </button>
                    <x-theme-picker class="pc-sidebar-theme" solid />
                    <div id="userMenu" class="pc-dropdown-menu pc-dropdown-menu-dark" role="menu" aria-hidden="true">
                        <div class="pc-dropdown-header">
                            <x-avatar :user="$user" />
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate font-semibold text-pc-sidebar-strong">{{ $user->name }}</span>
                                <span class="truncate text-xs text-pc-sidebar-muted">{{ $user->email }}</span>
                            </div>
                        </div>
                        @isset($currentWorkspace)
                            <div class="pc-dropdown-divider"></div>
                            @if (\App\Support\Workspaces::multiple())
                                {{-- multiple workspaces: every workspace the user is in, the current one ticked --}}
                                @foreach ($user->workspaces()->orderBy('name')->get() as $workspace)
                                    <form method="POST" action="{{ route('workspaces.switch', $workspace) }}" onsubmit="submitForm(event)">
                                        <button type="submit" @class(['pc-dropdown-item w-full', 'pc-dropdown-item-active' => $workspace->is($currentWorkspace)]) role="menuitem" @disabled($workspace->is($currentWorkspace))>
                                            <i class="fa-solid fa-building" aria-hidden="true"></i><span class="truncate">{{ $workspace->name }}</span>
                                            @if ($workspace->is($currentWorkspace))<i class="fa-solid fa-check ms-auto" aria-label="{{ __('Current workspace') }}"></i>@endif
                                        </button>
                                    </form>
                                @endforeach
                            @else
                                <div class="pc-dropdown-item pointer-events-none" aria-label="{{ __('Workspace') }}">
                                    <i class="fa-solid fa-building" aria-hidden="true"></i><span class="truncate">{{ $currentWorkspace->name }}</span>
                                </div>
                            @endif
                        @endisset
                        <div class="pc-dropdown-divider"></div>
                        <a href="{{ route('profile') }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => request()->routeIs('profile')]) role="menuitem">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>{{ __('Profile') }}
                        </a>
                        <div class="pc-dropdown-divider"></div>
                        <form method="POST" action="{{ route('auth.logout') }}" onsubmit="submitForm(event)">
                            <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem">
                                <i class="fa-solid fa-right-from-bracket rtl:-scale-x-100" aria-hidden="true"></i>{{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
    </aside>

    <main class="pc-main">
        <div @class(['pc-sheet', 'pc-requires-xl' => $desktopOnly])>
            <header class="pc-page-head">
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon -ms-2 lg:hidden" data-pc-trigger="sidebar" aria-label="{{ __('Open navigation') }}">
                    <i class="fa-regular fa-bars text-xl" aria-hidden="true"></i>
                </button>
                @if ($subtitle)
                    <div class="min-w-0">
                        <h1 class="pc-page-title">{{ $title }}</h1>
                        <p class="pc-page-subtitle">{{ $subtitle }}</p>
                    </div>
                @else
                    <h1 class="pc-page-title">{{ $title }}</h1>
                @endif
                {{ $header ?? '' }}
            </header>

            <div id="pcContent" @class(['pc-content' => $contained, 'pc-content-flush' => ! $contained])>
                {{ $slot }}
            </div>

            @if($desktopOnly)
                {{-- Below xl (1280px): frosted over the sheet, never dismissible. Pages can explain why with <x-slot:screen-gate>. --}}
                <div class="pc-screen-gate" role="alertdialog" aria-modal="true" aria-labelledby="pcScreenGateTitle">
                    <div class="pc-screen-gate-panel">
                        <span class="pc-empty-icon"><i class="fa-regular fa-desktop text-[22px]" aria-hidden="true"></i></span>
                        <div id="pcScreenGateTitle" class="text-base font-semibold text-pc-text">{{ __(':page needs a larger screen', ['page' => $title ?? __('This page')]) }}</div>
                        <p class="max-w-xs">{{ $screenGate ?? __('This page works with several panels side by side.') }} {{ __('Open it on a laptop or desktop — at least 1280px wide.') }}</p>
                        <a href="{{ url('/') }}" class="pc-btn pc-btn-secondary mt-2">{{ __('Back to Home') }}</a>
                    </div>
                </div>
                {{-- Keep focus and keyboard out of the content while the gate is up. --}}
                <script>(function(m,c){var s=function(){c.inert=!m.matches};s();m.addEventListener("change",s)})(matchMedia("(min-width: 1280px)"),document.getElementById("pcContent"))</script>
            @endif
        </div>
    </main>
</div>

{{-- pc-core first, app.js last; defer keeps this order. Page-only components (pc-tabs,
     pc-popover, pc-table, pc-toggle) go in @push('body'). --}}
{{-- the current language's strings for pc.t() (pc-core.js) --}}
<script>window.pcStrings = @json(App\Support\Locales::strings(), JSON_FORCE_OBJECT);</script>
<script src="@asset('assets/js/pc-core.js')" defer></script>
<script src="@asset('assets/js/pc-theme.js')" defer></script>
<script src="@asset('assets/js/pc-toast.js')" defer></script>
<script src="@asset('assets/js/pc-dialog.js')" defer></script>
<script src="@asset('assets/js/pc-tooltip.js')" defer></script>
<script src="@asset('assets/js/pc-dropdown.js')" defer></script>
<script src="@asset('assets/js/pc-modal.js')" defer></script>
<script src="@asset('assets/js/pc-offcanvas.js')" defer></script>
<script src="@asset('assets/js/pc-select.js')" defer></script>
<script src="@asset('assets/js/pc-date.js')" defer></script>
<script src="@asset('assets/js/pc-sidebar.js')" defer></script>
<script src="@asset('assets/js/pc-scrollbar.js')" defer></script>
<script src="@asset('assets/js/pc-password.js')" defer></script>
<script src="@asset('assets/js/app.js')" defer></script>

@stack('body')
</body>
</html>
