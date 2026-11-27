{{--
    The Submissions tab: the ways into the form's responses. "All submissions", then the form's saved views (shared
    with everyone on it), each with how many responses it shows and its rules in words. New view opens the responses
    table with the filter panel open; a view's menu opens or deletes it.
--}}
<x-section id="formViews" :title="__('Submissions')" :description="__('Open every response, or a saved view of them.')">
    <x-slot:actions>
        <a href="{{ route('forms.responses', [$form, 'filter' => 1]) }}" class="pc-btn pc-btn-secondary pc-btn-sm">
            <i class="fa-regular fa-plus" aria-hidden="true"></i>{{ __('New view') }}
        </a>
    </x-slot:actions>

    <ul class="pc-section-list">
        <li>
            <a href="{{ route('forms.responses', $form) }}" class="pc-view-row">
                <span class="pc-view-icon" aria-hidden="true"><i class="fa-regular fa-layer-group text-base"></i></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-pc-ink">{{ __('All submissions') }}</span>
                    <span class="block truncate text-xs text-pc-muted">{{ __('Every response to this form') }}</span>
                </span>
                <span class="text-sm font-semibold text-pc-ink tabular-nums">{{ number_format($stats['total']) }}</span>
                <i class="fa-regular fa-chevron-right text-pc-muted rtl:-scale-x-100" aria-hidden="true"></i>
            </a>
        </li>
        @foreach ($views as $view)
            <li class="flex items-center gap-1">
                <a href="{{ route('forms.responses', [$form, 'view' => $view]) }}" class="pc-view-row min-w-0 flex-1">
                    <span class="pc-view-icon" aria-hidden="true"><i class="fa-regular fa-filter text-base"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-pc-ink">{{ $view->name }}</span>
                        @if ($view->description)<span class="block truncate text-xs text-pc-muted">{{ $view->description }}</span>@endif
                        @if ($view->summary)
                            <span class="mt-1.5 flex flex-wrap gap-1">
                                @foreach ($view->summary as $line)<span class="pc-badge pc-badge-neutral max-w-full truncate">{{ $line }}</span>@endforeach
                            </span>
                        @endif
                    </span>
                    <span class="hidden text-xs whitespace-nowrap text-pc-muted sm:block">{{ __('Updated :time', ['time' => $view->updated_at->diffForHumans()]) }}</span>
                    <span class="text-sm font-semibold text-pc-ink tabular-nums">{{ number_format($view->matches) }}</span>
                </a>
                @if ($view->canChange)
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#viewMenu-{{ $view->hash }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Actions for :name', ['name' => $view->name]) }}">
                            <i class="fa-regular fa-ellipsis-vertical text-base" aria-hidden="true"></i>
                        </button>
                        <div id="viewMenu-{{ $view->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                            <a href="{{ route('forms.responses', [$form, 'view' => $view, 'filter' => 1]) }}" class="pc-dropdown-item" role="menuitem">
                                <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Edit filters') }}
                            </a>
                            <div class="pc-dropdown-divider"></div>
                            <form method="POST" action="{{ route('forms.views.destroy', [$form, $view]) }}" onsubmit="submitForm(event)">
                                <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                    data-pc-confirm="{{ __('Delete :name?', ['name' => $view->name]) }}" data-pc-confirm-text="{{ __('The view goes; its responses stay.') }}"
                                    data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                                    <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($views->isEmpty())
        <p class="mt-3 text-[13px] text-pc-muted">{{ __('Save a view to come back to a slice of responses, for example last month\'s, or one village\'s.') }}</p>
    @endif
</x-section>
