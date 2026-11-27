{{-- The space's members tab: Managers change roles and remove people here, and add them (partials/add-member) --}}
<x-section id="spaceMembers" :title="__('Members')" :description="__('Managers run the space; Editors create and edit its forms; Viewers can look but not change.')">
    @if ($canManage && $candidates->isNotEmpty())
        <x-slot:actions>
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-pc-trigger="modal" data-pc-target="#addMember">
                <i class="fa-regular fa-user-plus" aria-hidden="true"></i>{{ __('Add member') }}
            </button>
        </x-slot:actions>
    @endif
    <ul class="pc-section-list">
        @foreach ($space->members as $member)
            <li class="pc-section-row">
                <x-avatar :user="$member" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-pc-ink">
                        {{ $member->name }}
                        @if ($member->is(auth()->user()))<span class="text-xs font-normal text-pc-muted">({{ __('you') }})</span>@endif
                        @if ($space->isCreator($member))<span class="text-xs font-normal text-pc-muted">· {{ __('Creator') }}</span>@endif
                    </div>
                    <div class="truncate text-xs text-pc-muted">{{ $member->email }}</div>
                </div>
                {{-- the creator's role and your own are fixed (Space::roleLockedFor): shown, not changeable --}}
                @if ($canManage && ! $space->roleLockedFor($member, auth()->user()))
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-pc-trigger="dropdown" data-pc-target="#memberMenu-{{ $member->hash }}" aria-haspopup="menu" aria-expanded="false">
                            {{ $member->pivot->role->label() }}<i class="fa-regular fa-chevron-down text-xs" aria-hidden="true"></i>
                        </button>
                        <div id="memberMenu-{{ $member->hash }}" class="pc-dropdown-menu pc-dropdown-menu-end w-72" role="menu" aria-hidden="true">
                            @foreach (App\Enums\SpaceRole::cases() as $role)
                                <form method="POST" action="{{ route('spaces.members.update', [$space, $member]) }}" onsubmit="submitForm(event)">
                                    <input type="hidden" name="role" value="{{ $role->value }}">
                                    <button type="submit" @class(['pc-dropdown-item h-auto w-full py-2', 'pc-dropdown-item-active' => $member->pivot->role === $role]) role="menuitem" data-pc-dismiss="dropdown">
                                        <span class="flex min-w-0 flex-col items-start text-start">
                                            <span>{{ $role->label() }}</span>
                                            <span class="text-xs text-pc-muted">{{ $role->description() }}</span>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                            <div class="pc-dropdown-divider"></div>
                            <form method="POST" action="{{ route('spaces.members.destroy', [$space, $member]) }}" onsubmit="submitForm(event)">
                                <button type="submit" class="pc-dropdown-item pc-dropdown-item-danger w-full" role="menuitem" data-pc-dismiss="dropdown">
                                    <i class="fa-regular fa-user-minus text-base" aria-hidden="true"></i>{{ __('Remove from space') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <span class="text-[13px] text-pc-text-2">{{ $member->pivot->role->label() }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</x-section>
