{{-- Spaces (SpacesController@index): the spaces the user is in, every space for the workspace's managers --}}
<x-layout-app :title="__('Spaces')">
    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <button type="button" class="pc-btn pc-btn-primary" data-pc-trigger="modal" data-pc-target="#addSpace">
                <i class="fa-regular fa-plus" aria-hidden="true"></i><span class="pc-btn-label">{{ __('New space') }}</span>
            </button>
        </div>
    </x-slot:header>

    <x-table :paginator="$spaces" :label="__('Spaces')" :filterable="$hasSpaces">
        <x-slot:search>
            <x-search :placeholder="__('Search spaces')" />
        </x-slot:search>

        <x-slot:bulk>
            <form method="POST" action="{{ route('spaces.destroy-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="spaces">
                <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown" data-pc-confirm="{{ __('Delete the selected?') }}"
                    data-pc-bulk-confirm="{{ __('Delete :count space?|Delete :count spaces?') }}" data-pc-confirm-text="{{ __('Their forms stay; they just leave these spaces.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                    <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete') }}
                </button>
            </form>
        </x-slot:bulk>

        <x-slot:head>
            <th>{{ __('Space') }}</th>
            <th class="pc-col-more">{{ __('Your role') }}</th>
            <th class="pc-col-more">{{ __('Members') }}</th>
            <th class="pc-col-hide">{{ __('Forms') }}</th>
            <th class="w-12"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        <x-slot:empty>
            @if (request()->filled('q'))
                <x-empty icon="magnifying-glass" :title="__('No spaces match')">
                    <div class="max-w-xs">{{ __('Try another search.') }}</div>
                </x-empty>
            @else
                <x-empty icon="folders" :title="__('No spaces yet')">
                    <div class="max-w-xs">{{ __('Spaces group forms into projects and decide who can work on them.') }}</div>
                </x-empty>
            @endif
        </x-slot:empty>

        @foreach ($spaces as $space)
            @php($role = $space->members->first()?->pivot->role)
            <tr>
                <x-table.select :value="$space->hash" :label="$space->name" :disabled="! auth()->user()->can('delete', $space)" />
                <td>
                    <a href="{{ route('spaces.show', $space) }}" class="flex items-center gap-3 text-pc-text no-underline hover:text-pc-ink">
                        <span class="pc-space-tile pc-space-{{ $space->color ?? 'neutral' }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($space->name, 0, 1)) }}</span>
                        <span class="min-w-0">
                            <span class="block truncate font-bold text-pc-ink">{{ $space->name }}</span>
                            @if ($space->description)<span class="block truncate text-xs text-pc-muted">{{ $space->description }}</span>@endif
                        </span>
                    </a>
                </td>
                <td class="pc-col-more">
                    @if ($role)<span class="text-[13px] text-pc-text-2">{{ $role->label() }}</span>@else<span class="text-xs text-pc-muted">{{ __('Workspace admin') }}</span>@endif
                </td>
                <td class="pc-col-more text-[13px] text-pc-text-2">{{ trans_choice(':count member|:count members', $space->members_count) }}</td>
                <td class="pc-col-hide text-[13px] text-pc-text-2">{{ trans_choice(':count form|:count forms', $space->forms_count) }}</td>
                <td class="text-end">
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#spaceMenu-{{ $space->hash }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Actions for :name', ['name' => $space->name]) }}">
                            <i class="fa-regular fa-ellipsis-vertical text-base" aria-hidden="true"></i>
                        </button>
                        <div id="spaceMenu-{{ $space->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                            <a href="{{ route('spaces.show', $space) }}" class="pc-dropdown-item" role="menuitem">
                                <i class="fa-regular fa-arrow-up-right-from-square text-base" aria-hidden="true"></i>{{ __('Open') }}
                            </a>
                            <a href="{{ route('spaces.show', $space) }}#spaceMembers" class="pc-dropdown-item" role="menuitem">
                                <i class="fa-regular fa-users text-base" aria-hidden="true"></i>{{ __('Members') }}
                            </a>
                            @can('update', $space)
                                {{-- the payload fills the shared edit modal (app.js [data-pc-edit]) --}}
                                @php($editPayload = ['action' => route('spaces.update', $space), 'subtitle' => $space->name, 'fields' => ['name' => $space->name, 'description' => $space->description, 'color' => $space->color]])
                                <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown"
                                    data-pc-edit="#editSpace" data-pc-payload="{{ json_encode($editPayload) }}">
                                    <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Edit') }}
                                </button>
                            @endcan
                            @can('delete', $space)
                                <div class="pc-dropdown-divider"></div>
                                <form method="POST" action="{{ route('spaces.destroy', $space) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                        data-pc-confirm="{{ __('Delete :name?', ['name' => $space->name]) }}" data-pc-confirm-text="{{ __('Its forms stay; they just leave this space.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                                        <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>{{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @include('app.spaces.partials.add-space')
    @include('app.spaces.partials.update-space')
</x-layout-app>
