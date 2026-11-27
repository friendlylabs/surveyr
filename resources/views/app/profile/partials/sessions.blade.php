{{-- Browser sessions: this user's live sessions, this device first; log out one or the rest (pages/profile.js) --}}
<x-section id="sessions" :title="__('Browser sessions')" :description="__('The devices signed in to your account. If you don\'t recognise one, log it out and change your password.')" split>
    @if ($sessions->count() > 1)
        <x-slot:actions>
            <button type="button" class="pc-btn pc-btn-soft-danger pc-btn-sm" data-session-action="others">{{ __('Log out other devices') }}</button>
        </x-slot:actions>
    @endif
    @if (! $sessionsListed)
        <div class="text-[13px] text-pc-muted">{{ __('Sessions are listed when they\'re kept in the database (SESSION_DRIVER=database).') }}</div>
    @else
        <ul class="pc-section-list">
            @foreach ($sessions as $session)
                <li class="pc-section-row" data-session-row>
                    <span class="relative flex-none">
                        <span class="pc-empty-icon mb-0 size-9"><i class="fa-regular fa-{{ $session->mobile ? 'mobile-screen' : 'desktop' }} text-lg" aria-hidden="true"></i></span>
                        {{-- this device: a green dot on the icon's bottom-left --}}
                        @if ($session->current)
                            <span class="pc-dot pc-dot-sm pc-dot-success absolute bottom-0 start-0 box-content size-2 border-2 border-pc-sheet" data-pc-tooltip="{{ __('This device') }}"></span>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-medium">
                            {{ $session->device }}
                            @if ($session->current)<span class="sr-only">{{ __('(this device)') }}</span>@endif
                        </div>
                        <div class="font-pc-mono text-[11px] text-pc-muted">
                            {{ $session->ip_address ?? __('Unknown IP') }} · {{ $session->current ? __('Active now') : __('Last active :time', ['time' => $session->last_active->diffForHumans()]) }}
                        </div>
                    </div>
                    @unless ($session->current)
                        <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-session-action="logout" data-session-id="{{ $session->id }}">{{ __('Log out') }}</button>
                    @endunless
                </li>
            @endforeach
        </ul>
    @endif
</x-section>
