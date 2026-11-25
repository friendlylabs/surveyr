<x-layout-app :title="__('Users')">
    {{-- page actions sit in the page head, beside the title --}}
    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <button type="button" class="pc-btn pc-btn-primary" data-pc-trigger="modal" data-pc-target="#addUser">
                <i class="ph ph-plus" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Add user') }}</span>
            </button>
        </div>
    </x-slot:header>

    <x-table :paginator="$users" :label="__('Users')">
        {{-- filter order: text / number inputs, then selects, then other controls (tabs) --}}
        <x-slot:search>
            <x-search :placeholder="__('Search name or email')" />
        </x-slot:search>

        <x-slot:filters>
            <x-filter name="status" :options="$statuses" :all="__('Any status')" />

            <nav class="pc-tabs" aria-label="{{ __('Filter by role') }}">
                <a href="{{ route('admin.users', request()->only('q', 'status')) }}" @class(['pc-tab', 'pc-tab-active' => ! $role && ! $noRole]) @if (! $role && ! $noRole) aria-current="page" @endif>
                    {{ __('All') }} <span class="pc-tab-count">{{ $roleCounts->sum() }}</span>
                </a>
                @foreach ($roles as $r)
                    <a href="{{ route('admin.users', request()->only('q', 'status') + ['role' => $r->name]) }}" @class(['pc-tab', 'pc-tab-active' => $role?->is($r)]) @if ($role?->is($r)) aria-current="page" @endif>
                        {{ $r->label }} <span class="pc-tab-count">{{ $roleCounts[$r->id] ?? 0 }}</span>
                    </a>
                @endforeach
                @if ($noRole || ($roleCounts[''] ?? 0))
                    <a href="{{ route('admin.users', request()->only('q', 'status') + ['role' => 'none']) }}" @class(['pc-tab', 'pc-tab-active' => $noRole]) @if ($noRole) aria-current="page" @endif>
                        {{ __('No role') }} <span class="pc-tab-count">{{ $roleCounts[''] ?? 0 }}</span>
                    </a>
                @endif
            </nav>
        </x-slot:filters>

        <x-slot:head>
            <th>{{ __('Name') }}</th>
            <th class="pc-col-more">{{ __('Role') }}</th>
            <th class="pc-col-more">{{ __('Email') }}</th>
            <th class="pc-col-hide">{{ __('Added') }}</th>
            <th class="w-12"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        <x-slot:empty>
            @if (request()->hasAny(['q', 'role', 'status']))
                <x-empty icon="magnifying-glass" :title="__('No users match')">
                    <div class="max-w-xs">{{ __('Try another name or email, or clear the filters.') }}</div>
                    <a href="{{ route('admin.users') }}" class="pc-btn pc-btn-secondary mt-2">{{ __('Clear filters') }}</a>
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
                    'fields' => ['name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id],
                ];
            @endphp
            <tr>
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
                        <span class="pc-badge {{ $roleBadges[$user->role->name] ?? 'pc-badge-neutral' }}">{{ $user->role->label }}</span>
                    @else
                        <span class="text-pc-muted">{{ __('No role') }}</span>
                    @endif
                </td>
                <td class="pc-col-more">
                    @if ($user->hasVerifiedEmail())
                        <span class="pc-badge pc-badge-success"><span class="pc-dot pc-dot-sm pc-dot-success"></span>{{ __('Confirmed') }}</span>
                    @else
                        <span class="pc-badge pc-badge-warning"><span class="pc-dot pc-dot-sm pc-dot-warning"></span>{{ __('Unconfirmed') }}</span>
                    @endif
                </td>
                <td class="pc-col-hide text-[13px] whitespace-nowrap text-pc-muted">{{ $user->created_at?->format('d M Y') }}</td>
                <td class="text-end">
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#rowMenu-{{ $user->hash }}" aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('Actions for :name', ['name' => $user->name]) }}">
                            <i class="ph ph-dots-three-vertical text-base" aria-hidden="true"></i>
                        </button>
                        <div id="rowMenu-{{ $user->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end" role="menu" aria-hidden="true">
                            <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown"
                                data-pc-edit="#editUser" data-pc-payload="{{ json_encode($editPayload) }}">
                                <i class="ph ph-pencil-simple text-base" aria-hidden="true"></i>{{ __('Edit') }}
                            </button>
                            <form method="POST" action="{{ route('admin.users.password-link', $user) }}" onsubmit="submitForm(event)">
                                <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                                    <i class="ph ph-key text-base" aria-hidden="true"></i>{{ __('Send password link') }}
                                </button>
                            </form>
                            @unless ($user->is(auth()->user()))
                                <div class="pc-dropdown-divider"></div>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="submitForm(event)">
                                    <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown"
                                        data-pc-confirm="{{ __('Delete :name?', ['name' => $user->name]) }}" data-pc-confirm-text="{{ __('They lose access straight away. This can\'t be undone.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
                                        <i class="ph ph-trash text-base" aria-hidden="true"></i>{{ __('Delete') }}
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @include('admin.users.partials.add')
    @include('admin.users.partials.update')
</x-layout-app>
