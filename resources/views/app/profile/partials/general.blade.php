{{-- Profile information: name and email (ProfileController@update), a submitForm() form --}}
<section id="information" class="pc-card" aria-labelledby="informationTitle">
    <h2 id="informationTitle" class="pc-card-header text-sm">Profile information</h2>
    <form method="POST" action="{{ route('profile.update') }}" onsubmit="submitForm(event)" class="pc-card-body flex flex-col gap-4" novalidate>
        @csrf
        <div class="flex items-center gap-3">
            <x-avatar :name="$user->name" size="lg" />
            <div class="min-w-0">
                <div class="truncate font-semibold">{{ $user->name }}</div>
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    @if ($user->role)<span class="pc-badge pc-badge-neutral">{{ $user->role->label }}</span>@endif
                    @if ($user->hasVerifiedEmail())
                        <span class="pc-badge pc-badge-success"><i class="ph ph-seal-check" aria-hidden="true"></i>Email confirmed</span>
                    @else
                        <span class="pc-badge pc-badge-warning">Email unconfirmed</span>
                    @endif
                    <span class="text-xs text-pc-muted">Member since {{ $user->created_at?->format('j M Y') }}</span>
                </div>
            </div>
        </div>

        <div class="flex max-w-100 flex-col gap-4">
            <x-form.input id="profileName" name="name" label="Full name" :value="$user->name" required autocomplete="name" />
            <x-form.input id="profileEmail" name="email" type="email" label="Email" :value="$user->email" required autocomplete="email"
                :hint="Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) ? 'Change it and we\'ll email the new address a link to confirm it.' : null" />
        </div>

        <div>
            <button type="submit" class="pc-btn pc-btn-primary">Save changes</button>
        </div>
    </form>
</section>
