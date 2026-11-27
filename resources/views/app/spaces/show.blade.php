{{--
    A space (SpacesController@show): its description under the name, then tabs (the open one follows ?tab=) for its
    forms, its members and, for Managers (and workspace managers), its settings: edit it or delete it.
--}}
<x-layout-app :title="$space->name" :subtitle="$space->description">
    @php
        $tabs = ['forms' => [__('Forms'), $formsCount], 'members' => [__('Members'), $space->members->count()]];
        if ($canManage) {
            $tabs['settings'] = [__('Settings'), null];
        }
    @endphp

    <div class="flex w-full flex-col pb-6">
        <div data-pc-tabs-group data-pc-tabs-panels="#spacePanels" data-pc-tabs-query="tab" class="pc-tabs-underline mb-5" role="tablist" aria-label="{{ $space->name }}">
            @foreach ($tabs as $value => [$label, $count])
                <button type="button" data-pc-trigger="tabs" data-pc-value="{{ $value }}" id="spaceTab-{{ $value }}" @class(['pc-tab', 'pc-tab-active' => $tab === $value])
                    role="tab" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" aria-controls="spacePanel-{{ $value }}" @unless ($tab === $value) tabindex="-1" @endunless>
                    {{ $label }} @isset($count)<span class="pc-tab-count">{{ $count }}</span>@endisset
                </button>
            @endforeach
        </div>

        <div id="spacePanels">
            <div id="spacePanel-forms" data-pc-tab-panel="forms" data-pc-open="{{ $tab === 'forms' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="spaceTab-forms">
                @include('app.spaces.partials.forms')
            </div>
            {{-- the forms list runs full width; members keep the narrower reading width --}}
            <div id="spacePanel-members" class="max-w-5xl" data-pc-tab-panel="members" data-pc-open="{{ $tab === 'members' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="spaceTab-members">
                @include('app.spaces.partials.members')
            </div>
            @if ($canManage)
                <div id="spacePanel-settings" class="max-w-5xl" data-pc-tab-panel="settings" data-pc-open="{{ $tab === 'settings' ? 'true' : 'false' }}" role="tabpanel" aria-labelledby="spaceTab-settings">
                    @include('app.spaces.partials.settings')
                </div>
            @endif
        </div>
    </div>

    @if ($canManage && $candidates->isNotEmpty())
        @include('app.spaces.partials.add-member')
    @endif

    @push('body')
        <script src="@asset('assets/js/pc-tabs.js')" defer></script>
    @endpush
</x-layout-app>
