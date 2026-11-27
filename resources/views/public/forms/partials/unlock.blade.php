{{-- The access code step (Public\FormsController@unlock): the right code opens the form for the browser session --}}
<main class="pc-public-notice">
    <div class="pc-public-notice-card">
        <span class="pc-empty-icon"><i class="fa-regular fa-key text-[22px]" aria-hidden="true"></i></span>
        <h1 class="pc-public-notice-title">{{ $form->title }}</h1>
        <p>{{ __('Enter the access code you were given to open this form.') }}</p>
        <form method="POST" action="{{ route('public.forms.unlock', $form) }}" class="mt-2 flex w-full flex-col gap-3 text-start">
            @csrf
            <div>
                <label class="pc-label" for="accessCode">{{ __('Access code') }}</label>
                <input id="accessCode" name="access_code" type="password" class="pc-input" autocomplete="off" required autofocus
                    @error('access_code') aria-invalid="true" @enderror aria-describedby="accessCodeError">
                @error('access_code')<div id="accessCodeError" class="pc-field-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="pc-btn pc-btn-primary w-full">{{ __('Open form') }}</button>
        </form>
    </div>
</main>
