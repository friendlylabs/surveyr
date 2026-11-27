<x-layout-app :title="__('Users')">
    {{-- page actions sit in the page head, beside the title --}}
    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <button type="button" class="pc-btn pc-btn-primary" data-pc-trigger="modal" data-pc-target="#addUser">
                <i class="fa-regular fa-plus" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Add user') }}</span>
            </button>
        </div>
    </x-slot:header>

    <x-table :paginator="$users" :label="__('Users')">
        {{-- filter order: text / number inputs, then selects, then other controls --}}
        <x-slot:search>
            <x-search :placeholder="__('Search name or email')" />
        </x-slot:search>

        <x-slot:filters>
            <x-filter name="role" :options="$roleFilter" :all="__('Any role')" />
            <x-filter name="status" :options="$statuses" :all="__('Any status')" />
        </x-slot:filters>

        {{-- users are never deleted, only disabled (their forms and data stay); each action shows for the ticked rows it fits --}}
        <x-slot:bulk>
            <form method="POST" action="{{ route('admin.users.disable-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="users" data-pc-bulk-for="disabled">
                <input type="hidden" name="enable" value="1">
                <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                    <i class="fa-regular fa-user-check text-base" aria-hidden="true"></i>{{ __('Enable') }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.users.disable-many') }}" onsubmit="submitForm(event)" data-pc-bulk-form="users" data-pc-bulk-for="active">
                <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown" data-pc-confirm="{{ __('Disable the selected?') }}"
                    data-pc-bulk-confirm="{{ __('Disable :count user?|Disable :count users?') }}" data-pc-confirm-text="{{ __('They are signed out and can\'t sign in until enabled again. Their forms and data stay.') }}" data-pc-confirm-icon="warning" data-pc-confirm-button="{{ __('Disable') }}">
                    <i class="fa-regular fa-ban text-base" aria-hidden="true"></i>{{ __('Disable') }}
                </button>
            </form>
        </x-slot:bulk>

        <x-slot:head>
            <th>{{ __('Name') }}</th>
            <th class="pc-col-more">{{ __('Role') }}</th>
            <th class="pc-col-more">{{ __('Status') }}</th>
            <th class="pc-col-more">{{ __('Last sign-in') }}</th>
            <th class="pc-col-hide">{{ __('Added') }}</th>
            <th class="w-12"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        <x-slot:empty>
            @if (request()->hasAny(['q', 'role', 'status']))
                <x-empty icon="magnifying-glass" :title="__('No users match')">
                    <div class="max-w-xs">{{ __('Try another name or email, or clear the filters.') }}</div>
                </x-empty>
            @else
                <x-empty icon="users" :title="__('No users yet')">
                    <div class="max-w-xs">{{ __('Invite people with the Add user button.') }}</div>
                </x-empty>
            @endif
        </x-slot:empty>

        @foreach ($users as $user)
            @php
                # read by app.js [data-pc-edit] to fill the shared edit modal
                $editPayload = [
                    'action' => route('admin.users.update', $user),
                    'title' => $user->name,
                    'subtitle' => __('Added :date', ['date' => $user->created_at?->format('d M Y')]),
                    'fields' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role?->hash],
                ];
            @endphp
            <tr>
                <x-table.select :value="$user->hash" :label="$user->name" :disabled="$user->is(auth()->user())" :state="$user->isDisabled() ? 'disabled' : 'active'" />
                <td>
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div class="min-w-0">
                            <div class="truncate font-bold text-pc-ink">
                                {{ $user->name }}
                                @if ($user->is(auth()->user()))<span class="text-xs font-normal text-pc-muted">{{ __('(you)') }}</span>@endif
                            </div>
                            <div class="truncate text-xs text-pc-muted">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="pc-col-more">
                    @if ($user->role)
                        <span @class(['text-[13px]', 'font-semibold text-pc-ink' => $user->role->name === 'admin', 'text-pc-text-2' => $user->role->name !== 'admin'])>{{ $user->role->label }}</span>
                    @else
                        <span class="text-[13px] text-pc-muted">{{ __('No role') }}</span>
                    @endif
                </td>
                <td class="pc-col-more">
                    @if ($user->isDisabled())
                        <span class="pc-status pc-status-muted"><span class="pc-dot pc-dot-sm"></span>{{ __('Disabled') }}</span>
                    @elseif ($confirmsEmail && ! $user->hasVerifiedEmail())
                        <span class="pc-status pc-status-warning"><span class="pc-dot pc-dot-sm"></span>{{ __('Unconfirmed email') }}</span>
                    @else
                        <span class="pc-status pc-status-success"><span class="pc-dot pc-dot-sm"></span>{{ __('Active') }}</span>
                    @endif
                </td>
                <td class="pc-col-more text-[13px] whitespace-nowrap">
                    @if ($user->last_login_at)
                        <span class="text-pc-text-2" title="{{ $user->last_login_at->format('j M Y, H:i') }}">{{ $user->last_login_at->diffForHumans() }}</span>
                    @else
                        <span class="text-pc-muted">{{ __('Never') }}</span>
                    @endif
                </td>
                <td class="pc-col-hide text-[13px] whitespace-nowrap text-pc-muted">{{ $user->created_at?->format('d M Y') }}</td>
                <td class="text-end">
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#rowMenu-{{ $user->hash }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Actions for :name', ['name' => $user->name]) }}">
                            <i class="fa-regular fa-ellipsis-vertical text-base" aria-hidden="true"></i>
                        </button>
                        <div id="rowMenu-{{ $user->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                            <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown"
                                data-pc-edit="#editUser" data-pc-payload="{{ json_encode($editPayload) }}">
                                <i class="fa-regular fa-pen text-base" aria-hidden="true"></i>{{ __('Edit') }}
                            </button>
                            @unless ($user->isDisabled())
                                <form method="POST" action="{{ route('admin.users.password-link', $user) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                        <i class="fa-regular fa-key text-base" aria-hidden="true"></i>{{ __('Send password link') }}
                                    </button>
                                </form>
                            @endunless
                            @if ($user->isDisabled())
                                <form method="POST" action="{{ route('admin.users.disable', $user) }}" onsubmit="submitForm(event)">
                                    <input type="hidden" name="enable" value="1">
                                    <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                        <i class="fa-regular fa-user-check text-base" aria-hidden="true"></i>{{ __('Enable') }}
                                    </button>
                                </form>
                            @elseif (! $user->is(auth()->user()))
                                <div class="pc-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.users.disable', $user) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                        data-pc-confirm="{{ __('Disable :name?', ['name' => $user->name]) }}" data-pc-confirm-text="{{ __('They are signed out and can\'t sign in until enabled again. Their forms and data stay.') }}" data-pc-confirm-icon="warning" data-pc-confirm-button="{{ __('Disable') }}">
                                        <i class="fa-regular fa-ban text-base" aria-hidden="true"></i>{{ __('Disable') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @include('admin.users.partials.add')
    @include('admin.users.partials.update')
</x-layout-app>
