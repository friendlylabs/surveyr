{{-- Profile information: name, email and language (ProfileController@update), a submitForm() form --}}
<x-section id="information" :title="__('Profile information')" :description="__('Your name, email address and the language the app uses.')" split>
    <form method="POST" action="{{ route('profile.update') }}" onsubmit="submitForm(event)" class="flex flex-col gap-4" novalidate>
        @csrf
        <div class="flex items-center gap-3">
            <x-avatar :user="$user" size="lg" />
            <div class="min-w-0">
                <div class="truncate font-semibold">{{ $user->name }}</div>
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    @if ($user->role)<span class="truncate text-xs text-pc-sidebar-muted">{{ $user->role->label }}</span>@endif
                </div>
            </div>
        </div>

        <div class="flex max-w-100 flex-col gap-4">
            <x-form.input id="profileName" name="name" :label="__('Full name')" :value="$user->name" required autocomplete="name" />
            <x-form.input id="profileEmail" name="email" type="email" :label="__('Email')" :value="$user->email" required autocomplete="email"
                :hint="Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) ? __('Change it and we\'ll email the new address a link to confirm it.') : null" />
            {{-- interface language (config/surveyr.php `locales`); empty follows the workspace default --}}
            <x-form.select id="profileLocale" name="locale" :label="__('Language')" :selected="$user->locale"
                :placeholder="__('Workspace default (:language)', ['language' => App\Support\Locales::supported()[$user->currentWorkspace?->locale ?? App\Support\Locales::default()]['native'] ?? ''])"
                :options="collect(App\Support\Locales::supported())->map(fn ($locale) => $locale['native'])->all()" />
        </div>

        <div>
            <button type="submit" class="pc-btn pc-btn-primary">{{ __('Save changes') }}</button>
        </div>
    </form>
</x-section>
