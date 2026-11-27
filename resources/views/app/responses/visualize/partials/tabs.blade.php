{{--
    The tab bar along the bottom of Visualize, like a workbook's sheets: a link per tab (keeping the filters in force),
    and for editors a menu on the open tab (rename, move, delete). At the far end, after a divider, the saved view the
    charts count (switching keeps the open tab) and, for editors, a button to add a tab.
--}}
<nav class="pc-viz-tabs" aria-label="{{ __('Visualize tabs') }}" data-viz-print-hide>
        @foreach ($tabs as $item)
            @php($open = $tab?->is($item))
            <div @class(['pc-viz-tab', 'pc-viz-tab-active' => $open])>
                <a href="{{ route('forms.responses.visualize', [$form, ...$carried, 'tab' => $item->hash]) }}" class="pc-viz-tab-link" @if ($open) aria-current="page" @endif>{{ $item->name }}</a>
                @if ($open && $canEdit)
                    <div class="pc-dropdown">
                        <button type="button" class="pc-viz-tab-menu" data-pc-trigger="dropdown" data-pc-target="#tabMenu" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Tab options') }}">
                            <i class="fa-regular fa-chevron-up text-xs" aria-hidden="true"></i>
                        </button>
                        <div id="tabMenu" class="pc-dropdown-menu pc-dropdown-menu-up" role="menu" aria-hidden="true">
                            <button type="button" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" data-pc-trigger="modal" data-pc-target="#updateTab">
                                <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Rename') }}
                            </button>
                            <form method="POST" action="{{ route('forms.visualize.tabs.duplicate', [$form, $item]) }}" onsubmit="submitForm(event)">
                                <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                    <i class="fa-regular fa-copy text-base" aria-hidden="true"></i>{{ __('Duplicate') }}
                                </button>
                            </form>
                            @foreach ([-1 => [__('Move left'), 'arrow-left', $loop->first], 1 => [__('Move right'), 'arrow-right', $loop->last]] as $direction => [$label, $icon, $edge])
                                <form method="POST" action="{{ route('forms.visualize.tabs.move', [$form, $item]) }}" onsubmit="submitForm(event)">
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" @disabled($edge)>
                                        <i class="fa-regular fa-{{ $icon }} text-base rtl:-scale-x-100" aria-hidden="true"></i>{{ $label }}
                                    </button>
                                </form>
                            @endforeach
                            <div class="pc-dropdown-divider"></div>
                            <form method="POST" action="{{ route('forms.visualize.tabs.destroy', [$form, $item]) }}" onsubmit="submitForm(event)">
                                <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                    data-pc-confirm="{{ __('Delete :name?', ['name' => $item->name]) }}" data-pc-confirm-text="{{ __('The tab and its charts are deleted. The responses are untouched.') }}"
                                    data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                                    <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete tab') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    <div class="pc-viz-tabs-end">
        <div class="pc-dropdown">
            <button type="button" class="pc-btn pc-btn-ghost pc-btn-sm max-w-56" data-pc-trigger="dropdown" data-pc-target="#visualizeViews" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Saved view') }}" data-pc-tooltip="{{ __('Saved view') }}">
                <i class="fa-regular fa-layer-group text-sm" aria-hidden="true"></i>
                <span class="truncate">{{ $activeView?->name ?? __('All responses') }}</span><i class="fa-regular fa-chevron-up text-xs" aria-hidden="true"></i>
            </button>
            <div id="visualizeViews" class="pc-dropdown-menu pc-dropdown-menu-up pc-dropdown-menu-end" role="menu" aria-hidden="true">
                <a href="{{ route('forms.responses.visualize', array_filter([$form, 'archived' => $archived ? 1 : null, 'tab' => $tab?->hash])) }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => ! $activeView]) role="menuitem">{{ __('All responses') }}</a>
                @foreach ($views as $view)
                    <a href="{{ route('forms.responses.visualize', array_filter([$form, 'view' => $view->hash, 'archived' => $archived ? 1 : null, 'tab' => $tab?->hash])) }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => $activeView?->is($view)]) role="menuitem">
                        <span class="truncate">{{ $view->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @if ($canEdit)
            <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="modal" data-pc-target="#addTab" aria-label="{{ __('Add tab') }}" data-pc-tooltip="{{ __('Add tab') }}">
                <i class="fa-regular fa-plus text-base" aria-hidden="true"></i>
            </button>
        @endif
    </div>
</nav>
