{{-- The open form, drawn and sent by pages/public-form.js --}}
@push('head')
    <x-surveyjs entry="form" />
@endpush

<main id="publicForm" class="pc-public-form"></main>

{{-- read by pages/public-form.js; the hex flags keep a "</script>" typed into the form from closing this tag --}}
<script type="application/json" id="publicFormConfig">{!! json_encode([
    'content' => $form->content ?: (object) [],
    'theme' => $form->theme ?: (object) [],
    'submitUrl' => route('public.forms.submit', $form),
    'uploadUrl' => route('public.forms.upload', $form),
    'locale' => app()->getLocale(),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

@push('body')
    <script src="@asset('assets/js/pages/public-form.js')" defer></script>
@endpush
