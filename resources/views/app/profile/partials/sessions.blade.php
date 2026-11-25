{{-- Browser sessions: this user's live sessions, this device first; log out one or the rest (pages/profile.js) --}}
<section id="sessions" class="pc-card overflow-hidden" aria-labelledby="sessionsTitle">
    <h2 id="sessionsTitle" class="pc-card-header text-sm">
        Browser sessions
        @if ($sessions->count() > 1)
            <button type="button" class="pc-btn pc-btn-soft-danger pc-btn-sm font-normal" data-session-action="others">Log out other devices</button>
        @endif
    </h2>
    @if (! $sessionsListed)
        <div class="pc-card-body text-[13px] text-pc-muted">Sessions are listed when they're kept in the database (SESSION_DRIVER=database).</div>
    @else
        <p class="px-5 pt-4 text-[13px] text-pc-text-2">The devices signed in to your account. If you don't recognise one, log it out and change your password.</p>
        <ul class="flex flex-col py-2">
            @foreach ($sessions as $session)
                <li class="flex items-center gap-3 border-b border-pc-border-soft px-5 py-3 last:border-b-0" data-session-row>
                    <span class="relative flex-none">
                        <span class="pc-empty-icon mb-0 size-9"><i class="ph ph-{{ $session->mobile ? 'device-mobile' : 'desktop' }} text-lg" aria-hidden="true"></i></span>
                        {{-- this device: a green dot on the icon's bottom-left --}}
                        @if ($session->current)
                            <span class="pc-dot pc-dot-sm pc-dot-success absolute bottom-0 left-0 box-content size-2 border-2 border-white" data-pc-tooltip="This device"></span>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-medium">
                            {{ $session->device }}
                            @if ($session->current)<span class="sr-only">(this device)</span>@endif
                        </div>
                        <div class="font-pc-mono text-[11px] text-pc-muted">
                            {{ $session->ip_address ?? 'Unknown IP' }} · {{ $session->current ? 'Active now' : 'Last active '.$session->last_active->diffForHumans() }}
                        </div>
                    </div>
                    @unless ($session->current)
                        <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-session-action="logout" data-session-id="{{ $session->id }}">Log out</button>
                    @endunless
                </li>
            @endforeach
        </ul>
    @endif
</section>
