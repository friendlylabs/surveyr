{{-- Passkeys: the user's passkeys and the add form, shown only where the browser supports WebAuthn (pages/profile.js) --}}
<x-section id="passkeys" :title="__('Passkeys')" :description="__('Sign in without a password, with your fingerprint, face or a security key. Each passkey lives on one device.')" split>
    <ul id="passkeyList" class="pc-section-list">
        @foreach ($passkeys as $passkey)
            <li class="pc-section-row" data-passkey-row>
                <span class="pc-empty-icon mb-0 size-9 flex-none"><i class="ph ph-fingerprint text-lg" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[13px] font-medium">{{ $passkey->name }}</div>
                    <div class="font-pc-mono text-[11px] text-pc-muted">{{ __('Added :time', ['time' => $passkey->created_at->diffForHumans()]) }} · {{ $passkey->last_used_at ? __('last used :time', ['time' => $passkey->last_used_at->diffForHumans()]) : __('not used yet') }}</div>
                </div>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm text-pc-danger-strong" aria-label="{{ __('Remove :name', ['name' => $passkey->name]) }}" data-pc-tooltip="{{ __('Remove') }}" data-passkey-action="remove" data-passkey-id="{{ $passkey->id }}"><i class="ph ph-trash text-base" aria-hidden="true"></i></button>
            </li>
        @endforeach
        <li id="passkeyEmpty" class="pc-section-row text-[13px] text-pc-muted" @if ($passkeys->isNotEmpty()) hidden @endif>{{ __('No passkeys yet.') }}</li>
    </ul>
    {{-- hidden until pages/profile.js finds WebAuthn support; browsers without it get the note instead --}}
    <form id="passkeyForm" class="mt-4 flex flex-wrap items-center gap-2" novalidate hidden>
        <div class="w-64 max-sm:flex-1">
            <input id="passkeyName" name="name" type="text" class="pc-input" placeholder="{{ __('Name it, e.g. Work laptop') }}" maxlength="255" aria-label="{{ __('Passkey name') }}">
        </div>
        <button type="submit" class="pc-btn pc-btn-primary">{{ __('Add a passkey') }}</button>
    </form>
    <div id="passkeyUnsupported" class="mt-4 flex items-center gap-2 text-xs text-pc-muted" hidden>
        <i class="ph ph-info text-sm" aria-hidden="true"></i>{{ __('This browser can\'t add passkeys. Open this page in a recent Chrome, Edge, Safari or Firefox to add one.') }}
    </div>
</x-section>
