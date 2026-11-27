{{--
    A form's responses (FormResponsesController@index), as in the legacy app: every question a column, sortable and
    scrolling sideways; a row opens the response in a side panel (pages/responses.js); responses are archived, not
    deleted, with an Archived view (?archived=1) to restore from. The menu holds the archived view and the purge.
--}}
<x-layout-app :title="$form->title" :subtitle="($activeView ? $activeView->name.' · ' : '').($archived ? trans_choice(':count archived response|:count archived responses', $responses->total()) : trans_choice(':count response|:count responses', $responses->total()))">
    @push('head')
        <x-surveyjs entry="form" />
    @endpush

    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            @if ($archived)
                <a href="{{ route('forms.responses', $form) }}" class="pc-btn pc-btn-secondary">
                    <i class="fa-regular fa-arrow-left rtl:-scale-x-100" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Back to responses') }}</span>
                </a>
            @endif
            <div class="pc-dropdown">
                <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" data-pc-trigger="dropdown" data-pc-target="#responsesMenu" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('More actions') }}">
                    <i class="fa-regular fa-ellipsis" aria-hidden="true"></i>
                </button>
                <div id="responsesMenu" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                    @unless ($archived)
                        <a href="{{ route('forms.responses', [$form, 'archived' => 1]) }}" class="pc-dropdown-item" role="menuitem">
                            <i class="fa-regular fa-box-archive text-base" aria-hidden="true"></i>{{ __('Archive') }}
                            <span class="ms-auto ps-3 text-xs text-pc-muted tabular-nums">{{ number_format($archivedCount) }}</span>
                        </a>
                    @endunless
                    <a href="{{ route('forms.show', $form) }}" class="pc-dropdown-item" role="menuitem">
                        <i class="fa-regular fa-chart-line text-base" aria-hidden="true"></i>{{ __('Form overview') }}
                    </a>
                    @if ($canPurge)
                        <div class="pc-dropdown-divider"></div>
                        <form method="POST" action="{{ route('forms.responses.purge', $form) }}" onsubmit="submitForm(event)">
                            <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                data-pc-confirm="{{ __('Purge every response?') }}" data-pc-confirm-text="{{ __('All of this form\'s responses, archived ones too, are deleted for good. This can\'t be undone.') }}"
                                data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Purge') }}">
                                <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Purge responses') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            {{-- the filter panel (partials/filters); its count shows while filters apply --}}
            @if ($hasResponses || $filterCount)
                <button type="button" class="pc-btn pc-btn-primary relative" data-pc-trigger="offcanvas" data-pc-target="#responsesFilter">
                    <i class="fa-regular fa-filter text-base" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Filter') }}</span>
                    @if ($filterCount)<span class="pc-count-badge pc-btn-badge">{{ $filterCount }}</span>@endif
                </button>
            @endif
        </div>
    </x-slot:header>

    @include('app.responses.partials.table')
    @include('app.responses.partials.view')
    @include('app.responses.partials.filters')
    @include('app.responses.partials.save-view')
    @include('app.responses.partials.export')

    {{-- read by pages/responses.js; the hex flags keep a "</script>" typed into the form from closing this tag --}}
    <script type="application/json" id="responsesConfig">{!! json_encode([
        'content' => $form->content ?: (object) [],
        'theme' => $form->theme ?: (object) [],
        'showUrl' => route('forms.responses.show', [$form, '__response__']),
        'uploadUrl' => route('forms.responses.upload', [$form, '__response__', '__upload__']),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    {{-- read by pages/response-filters.js: the questions a rule can use, its operators, and the filters in force --}}
    <script type="application/json" id="responseFiltersConfig">{!! json_encode([
        'fields' => $fields ?: (object) [],
        'operators' => App\Support\SubmissionFilters::OPERATORS,
        'labels' => App\Support\SubmissionFilters::operatorLabels(),
        'filters' => $filters,
        'strings' => ['question' => __('Question'), 'value' => __('Value'), 'min' => __('Min'), 'max' => __('Max'), 'choose' => __('Choose…'), 'remove' => __('Remove rule')],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    @push('body')
        <script src="@asset('assets/js/pages/responses.js')" defer></script>
        <script src="@asset('assets/js/pages/response-filters.js')" defer></script>
        <script src="@asset('assets/js/pages/response-export.js')" defer></script>
    @endpush
</x-layout-app>
