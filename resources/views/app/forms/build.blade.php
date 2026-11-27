{{--
    The form builder (FormBuilderController@edit): the SurveyJS Creator filling the sheet, the sidebar collapsed.
    Changes autosave (pages/builder.js); the head says so while a change is unsaved, saving or failed, and nothing once
    saved. The Creator is responsive, so the page opens on any screen.
--}}
<x-layout-app :title="$form->title" :sidebar="false" :contained-layout="false">
    @push('head')
        <x-surveyjs entry="creator" />
    @endpush

    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <span class="pc-save-status" data-pc-save-status data-state="saved" role="status" aria-live="polite">
                <span class="pc-dot pc-dot-sm" aria-hidden="true"></span><span data-pc-save-text>{{ __('Saved') }}</span>
            </span>
            <a href="{{ route('forms.show', $form) }}" class="pc-btn pc-btn-primary">
                <i class="fa-regular fa-check" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Done') }}</span>
            </a>
        </div>
    </x-slot:header>

    @if ($form->hasResponses())
        <div class="pc-builder-notice" role="note">
            <i class="fa-regular fa-circle-exclamation text-base" aria-hidden="true"></i>
            {{ __('This form already has responses. Renaming or removing questions can leave earlier answers without a question.') }}
        </div>
    @endif

    {{-- the theme picker sits in the Creator, as the Theme category of the survey's settings (pages/builder.js) --}}
    <div id="formBuilder" class="pc-builder" aria-label="{{ __('Form builder') }}"></div>

    {{-- read by pages/builder.js; the hex flags keep a "</script>" typed into the form from closing this tag --}}
    <script type="application/json" id="formBuilderConfig">{!! json_encode([
        'content' => $form->content ?: (object) [],
        'revision' => $form->revision,
        'saveUrl' => route('forms.build.save', $form),
        'theme' => $form->theme ?: (object) [],
        'themeUrl' => route('forms.theme', $form),
        'assetUrl' => route('forms.assets', $form),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    @push('body')
        <script src="@asset('assets/js/pages/builder.js')" defer></script>
    @endpush
</x-layout-app>
