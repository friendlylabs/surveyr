{{-- Passkeys: the user's passkeys and the add form, shown only where the browser supports WebAuthn (pages/profile.js) --}}
<section id="passkeys" class="pc-card overflow-hidden" aria-labelledby="passkeysTitle">
    <h2 id="passkeysTitle" class="pc-card-header text-sm">Passkeys</h2>
    <p class="px-5 pt-4 text-[13px] text-pc-text-2">Sign in without a password, with your fingerprint, face or a security key. Each passkey lives on one device.</p>
    <ul id="passkeyList" class="flex flex-col py-2">
        @foreach ($passkeys as $passkey)
            <li class="flex items-center gap-3 border-b border-pc-border-soft px-5 py-3 last:border-b-0" data-passkey-row>
                <span class="pc-empty-icon mb-0 size-9 flex-none"><i class="ph ph-fingerprint text-lg" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[13px] font-medium">{{ $passkey->name }}</div>
                    <div class="font-pc-mono text-[11px] text-pc-muted">Added {{ $passkey->created_at->diffForHumans() }} · {{ $passkey->last_used_at ? 'last used '.$passkey->last_used_at->diffForHumans() : 'not used yet' }}</div>
                </div>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm text-pc-danger-strong" aria-label="Remove {{ $passkey->name }}" data-pc-tooltip="Remove" data-passkey-action="remove" data-passkey-id="{{ $passkey->id }}"><i class="ph ph-trash text-base" aria-hidden="true"></i></button>
            </li>
        @endforeach
        <li id="passkeyEmpty" class="px-5 py-3 text-[13px] text-pc-muted" @if ($passkeys->isNotEmpty()) hidden @endif>No passkeys yet.</li>
    </ul>
    {{-- hidden until pages/profile.js finds WebAuthn support; browsers without it get the note instead --}}
    <form id="passkeyForm" class="pc-card-footer justify-start" novalidate hidden>
        <div class="w-64 max-sm:flex-1">
            <input id="passkeyName" name="name" type="text" class="pc-input" placeholder="Name it, e.g. Work laptop" maxlength="255" aria-label="Passkey name">
        </div>
        <button type="submit" class="pc-btn pc-btn-primary">Add a passkey</button>
    </form>
    <div id="passkeyUnsupported" class="pc-card-footer justify-start text-xs text-pc-muted" hidden>
        <i class="ph ph-info text-sm" aria-hidden="true"></i>This browser can't add passkeys. Open this page in a recent Chrome, Edge, Safari or Firefox to add one.
    </div>
</section>
