{{--
    A forms list (the forms page, a space's Forms tab): search, status filter, bulk archive / restore / delete and each
    form's menu. Needs $forms (a paginator), $hasForms (false hides the search and filters), $statuses; optional
    $clearUrl (where Clear filters goes), $emptyTitle and $emptyText (when there are no forms at all), $showSpaces
    (false drops the Spaces column, e.g. inside a space).
--}}
@php
    $clearUrl ??= route('forms');
    $emptyTitle ??= __('No forms yet');
    $emptyText ??= __('Create a form to start collecting responses.');
    $showSpaces ??= true;
@endphp

<x-table :paginator="$forms" :label="__('Forms')" :filterable="$hasForms">
    {{-- filter order: text / number inputs, then selects, then other controls --}}
    <x-slot:search>
        <x-search :placeholder="__('Search forms')" />
    </x-slot:search>

    <x-slot:filters>
        <x-filter name="status" :options="$statuses" :all="__('Any status')" />
    </x-slot:filters>

    {{-- forms with data are archived, never deleted; Delete only goes through when none of the ticked ones has any --}}
    <x-slot:bulk>
        <form method="POST" action="{{ route('forms.archive-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="forms" data-pc-bulk-for="active">
            <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                <i class="fa-regular fa-box-archive text-base" aria-hidden="true"></i>{{ __('Archive') }}
            </button>
        </form>
        <form method="POST" action="{{ route('forms.archive-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="forms" data-pc-bulk-for="archived">
            <input type="hidden" name="restore" value="1">
            <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                <i class="fa-regular fa-arrow-rotate-left text-base" aria-hidden="true"></i>{{ __('Restore') }}
            </button>
        </form>
        <div class="pc-dropdown-divider"></div>
        <form method="POST" action="{{ route('forms.destroy-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="forms">
            <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown" data-pc-confirm="{{ __('Delete the selected?') }}"
                data-pc-bulk-confirm="{{ __('Delete :count form?|Delete :count forms?') }}" data-pc-confirm-text="{{ __('Only forms without responses can be deleted. This can\'t be undone.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete') }}
            </button>
        </form>
    </x-slot:bulk>

    <x-slot:head>
        <th>{{ __('Form') }}</th>
        @if ($showSpaces)<th class="pc-col-more">{{ __('Spaces') }}</th>@endif
        <th class="pc-col-hide">{{ __('Owner') }}</th>
        <th class="pc-col-more">{{ __('Status') }}</th>
        <th class="pc-col-more">{{ __('Responses') }}</th>
        <th class="pc-col-hide">{{ __('Modified') }}</th>
        <th class="w-12"><span class="sr-only">{{ __('Actions') }}</span></th>
    </x-slot:head>

    <x-slot:empty>
        @if (request()->hasAny(['q', 'status']))
            <x-empty icon="magnifying-glass" :title="__('No forms match')">
                <div class="max-w-xs">{{ __('Try another search, or clear the filters.') }}</div>
            </x-empty>
        @else
            <x-empty icon="clipboard-list" :title="$emptyTitle">
                <div class="max-w-xs">{{ $emptyText }}</div>
            </x-empty>
        @endif
    </x-slot:empty>

    @foreach ($forms as $form)
        <tr>
            <x-table.select :value="$form->hash" :label="$form->title" :disabled="! auth()->user()->can('archive', $form)" :state="$form->isArchived() ? 'archived' : 'active'" />
            @php($canEdit = auth()->user()->can('update', $form))
            <td>
                {{-- a form opens on its overview, as in Surveyr --}}
                <a href="{{ route('forms.show', $form) }}" class="flex items-center gap-3 text-pc-text no-underline hover:text-pc-ink">
                    <span class="pc-form-tile" aria-hidden="true">{{ mb_strtoupper(mb_substr($form->title, 0, 1)) }}</span>
                    <span class="min-w-0 max-w-[28rem]">
                        <span class="block truncate font-bold text-pc-ink">{{ $form->title }}</span>
                        @if ($form->description)<span class="block truncate text-xs text-pc-muted">{{ $form->description }}</span>@endif
                    </span>
                </a>
            </td>
            @if ($showSpaces)
            <td class="pc-col-more">
                <div class="text-[13px] text-pc-text-2">
                    @forelse ($form->spaces as $formSpace)
                        <a href="{{ route('spaces.show', $formSpace) }}" class="text-pc-text-2 no-underline hover:text-pc-ink hover:underline">{{ $formSpace->name }}</a>@unless ($loop->last), @endunless
                    @empty
                        <span class="text-pc-muted">{{ __('Personal') }}</span>
                    @endforelse
                </div>
            </td>
            @endif
            <td class="pc-col-hide">
                @if ($form->owner)
                    <span class="inline-flex items-center gap-2 text-[13px] text-pc-text-2"><x-avatar :user="$form->owner" size="sm" />{{ $form->owner->name }}</span>
                @endif
            </td>
            <td class="pc-col-more"><x-form-status :form="$form" /></td>
            <td class="pc-col-more">
                <a href="{{ route('forms.responses', $form) }}" @class(['text-[13px] tabular-nums no-underline hover:underline', 'font-semibold text-pc-ink' => $form->submissions_count, 'text-pc-muted' => ! $form->submissions_count])>{{ number_format($form->submissions_count) }}</a>
            </td>
            <td class="pc-col-hide text-[13px] whitespace-nowrap text-pc-muted" title="{{ $form->updated_at->format('j M Y, H:i') }}">{{ $form->updated_at->diffForHumans() }}</td>
            <td class="text-end">
                <div class="pc-dropdown">
                    <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#formMenu-{{ $form->hash }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Actions for :title', ['title' => $form->title]) }}">
                        <i class="fa-regular fa-ellipsis-vertical text-base" aria-hidden="true"></i>
                    </button>
                    <div id="formMenu-{{ $form->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                        @if ($canEdit)
                            <a href="{{ route('forms.build', $form) }}" class="pc-dropdown-item" role="menuitem">
                                <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Edit form') }}
                            </a>
                            <a href="{{ route('forms.show', [$form, 'tab' => 'settings']) }}" class="pc-dropdown-item" role="menuitem">
                                <i class="fa-regular fa-gear text-base" aria-hidden="true"></i>{{ __('Settings') }}
                            </a>
                        @endif
                        <a href="{{ route('forms.responses', $form) }}" class="pc-dropdown-item" role="menuitem">
                            <i class="fa-regular fa-inbox text-base" aria-hidden="true"></i>{{ __('Responses') }}
                        </a>
                        <a href="{{ route('forms.preview', $form) }}" class="pc-dropdown-item" role="menuitem">
                            <i class="fa-regular fa-eye text-base" aria-hidden="true"></i>{{ __('Preview') }}
                        </a>
                        {{-- the public form, as respondents reach it --}}
                        <a href="{{ route('public.forms.show', $form) }}" class="pc-dropdown-item" role="menuitem" target="_blank" rel="noopener">
                            <i class="fa-regular fa-arrow-up-right-from-square text-base" aria-hidden="true"></i>{{ __('Open form') }}
                        </a>
                        <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown" onclick="copyToClipboard('{{ route('public.forms.show', $form) }}')">
                            <i class="fa-regular fa-link text-base" aria-hidden="true"></i>{{ __('Copy link') }}
                        </button>
                        @can('archive', $form)
                            <div class="pc-dropdown-divider"></div>
                            @if ($form->isArchived())
                                <form method="POST" action="{{ route('forms.restore', $form) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                        <i class="fa-regular fa-arrow-rotate-left text-base" aria-hidden="true"></i>{{ __('Restore') }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('forms.archive', $form) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                        <i class="fa-regular fa-box-archive text-base" aria-hidden="true"></i>{{ __('Archive') }}
                                    </button>
                                </form>
                            @endif
                            @can('delete', $form)
                                <div class="pc-dropdown-divider"></div>
                                <form method="POST" action="{{ route('forms.destroy', $form) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                        data-pc-confirm="{{ __('Delete :title?', ['title' => $form->title]) }}" data-pc-confirm-text="{{ __('It has no responses yet, so nothing else is lost. This can\'t be undone.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                                        <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        @endcan
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
</x-table>
