{{-- Theme picker. A click saves right away (forms/scripts/setup). --}}
<div id="appearanceSettings">
    <div class="fo-card-head mb-3">
        <div>
            <h3>Theme</h3>
            <p>How the public form looks to respondents</p>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($themes as $theme)
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <button type="button" class="fo-theme {{ $form->theme == $theme->theme ? 'is-selected' : '' }}" data-theme="{{ $theme->theme }}" aria-pressed="{{ $form->theme == $theme->theme ? 'true' : 'false' }}">
                    <img src="{{ $theme->cover }}" alt="" loading="lazy">
                    <span class="fo-theme-name">
                        {{ $theme->name }}
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </button>
            </div>
        @endforeach
    </div>
</div>
