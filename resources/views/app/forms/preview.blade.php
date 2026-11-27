{{--
    A form as respondents see it (FormBuilderController@preview), for anyone who can see the form; completing it saves
    nothing (pages/preview.js). Editors get a way into the builder.
--}}
<x-layout-app :title="$form->title" :subtitle="__('Preview: answers aren\'t saved')" :contained-layout="false">
    @push('head')
        <x-surveyjs entry="form" />
    @endpush

    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            @can('update', $form)
                <a href="{{ route('forms.build', $form) }}" class="pc-btn pc-btn-primary">
                    <i class="fa-regular fa-pen" aria-hidden="true"></i><span class="pc-btn-label">{{ __('Edit form') }}</span>
                </a>
            @endcan
        </div>
    </x-slot:header>

    <div id="formPreview" class="pc-preview"></div>

    {{-- read by pages/preview.js; the hex flags keep a "</script>" typed into the form from closing this tag --}}
    <script type="application/json" id="formPreviewConfig">{!! json_encode(['content' => $form->content ?: (object) [], 'theme' => $form->theme ?: (object) []], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    @push('body')
        <script src="@asset('assets/js/pages/preview.js')" defer></script>
    @endpush
</x-layout-app>
