{{--
    A form's overview (FormOverviewController@show), the first screen when a form is opened: its description and
    actions, then tabs (the open one follows ?tab=): Overview, Submissions and, for those who can edit it, Settings.
--}}
<x-layout-app :title="$form->title" :subtitle="$form->description">
    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <div class="pc-dropdown">
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" data-pc-trigger="dropdown" data-pc-target="#formMoreMenu" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('More actions') }}">
                    <i class="fa-regular fa-ellipsis" aria-hidden="true"></i>
                </button>
                <div id="formMoreMenu" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                    <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-trigger="modal" data-pc-target="#shareForm">
                        <i class="fa-regular fa-share-nodes text-base" aria-hidden="true"></i>{{ __('Share') }}
                    </button>
                    <a href="{{ route('forms.preview', $form) }}" class="pc-dropdown-item" role="menuitem">
                        <i class="fa-regular fa-eye text-base" aria-hidden="true"></i>{{ __('Preview') }}
                    </a>
                    <a href="{{ route('public.forms.show', $form) }}" class="pc-dropdown-item" role="menuitem" target="_blank" rel="noopener">
                        <i class="fa-regular fa-arrow-up-right-from-square text-base" aria-hidden="true"></i>{{ __('Open form') }}
                    </a>
                </div>
            </div>
            @if ($canEdit)
                <a href="{{ route('forms.build', $form) }}" class="pc-btn pc-btn-primary">
                    <i class="fa-regular fa-pen" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Edit form') }}</span>
                </a>
            @endif
        </div>
    </x-slot:header>

    @php
        $tabs = ['overview' => [__('Overview'), null], 'submissions' => [__('Submissions'), $stats['total']]];
        if ($canEdit) {
            $tabs += ['settings' => [__('Settings'), null]];
        }
    @endphp

    <div class="flex w-full flex-col pb-6">
        <div data-pc-tabs-group data-pc-tabs-panels="#formPanels" data-pc-tabs-query="tab" class="pc-tabs-underline mb-5" role="tablist" aria-label="{{ $form->title }}">
            @foreach ($tabs as $value => [$label, $count])
                <button type="button" data-pc-trigger="tabs" data-pc-value="{{ $value }}" id="formTab-{{ $value }}" @class(['pc-tab', 'pc-tab-active' => $tab === $value])
                    role="tab" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" aria-controls="formPanel-{{ $value }}" @unless ($tab === $value) tabindex="-1" @endunless>
                    {{ $label }} @isset($count)<span class="pc-tab-count">{{ number_format($count) }}</span>@endisset
                </button>
            @endforeach
        </div>

        <div id="formPanels">
            <div id="formPanel-overview" data-pc-tab-panel="overview" data-pc-open="{{ $tab === 'overview' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="formTab-overview">
                @include('app.forms.partials.overview.stats')
                @include('app.forms.partials.overview.chart')
            </div>
            <div id="formPanel-submissions" class="max-w-5xl" data-pc-tab-panel="submissions" data-pc-open="{{ $tab === 'submissions' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="formTab-submissions">
                @include('app.forms.partials.overview.submissions')
            </div>
            @if ($canEdit)
                <div id="formPanel-settings" class="max-w-5xl" data-pc-tab-panel="settings" data-pc-open="{{ $tab === 'settings' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="formTab-settings">
                    <div class="pc-section-stack">
                        @include('app.forms.partials.settings.general')
                        @include('app.forms.partials.settings.access')
                        @include('app.forms.partials.settings.submission')
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('app.forms.partials.share')

    @push('body')
        <script src="@asset('assets/js/pc-tabs.js')" defer></script>
    @endpush
</x-layout-app>
