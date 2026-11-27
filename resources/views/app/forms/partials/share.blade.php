{{-- Share the form: its public link, and a snippet that embeds it in another site (panelless forms take on that site's background) --}}
@php
    $link = route('public.forms.show', $form);
    $embed = '<iframe src="'.$link.'" title="'.e($form->title).'" style="width:100%;min-height:640px;border:0" loading="lazy"></iframe>';
@endphp

<x-dialog.modal id="shareForm" :title="__('Share form')" :subtitle="$form->title">
    <div>
        <label class="pc-label" for="shareFormLink">{{ __('Link') }}</label>
        <div class="flex gap-2">
            <input id="shareFormLink" type="text" class="pc-input" value="{{ $link }}" readonly onfocus="this.select()">
            <button type="button" class="pc-btn pc-btn-secondary flex-none" onclick="copyToClipboard(document.getElementById('shareFormLink'))">
                <i class="fa-regular fa-copy" aria-hidden="true"></i>{{ __('Copy') }}
            </button>
        </div>
        @if ($form->status() !== 'open')
            <div class="pc-field-hint">{{ __('The form isn\'t taking responses right now; the link shows why.') }}</div>
        @endif
    </div>
    <div>
        <label class="pc-label" for="shareFormEmbed">{{ __('Embed on a website') }}</label>
        <textarea id="shareFormEmbed" class="pc-textarea font-pc-mono text-xs" rows="3" readonly onfocus="this.select()">{{ $embed }}</textarea>
        <div class="mt-2 flex items-center justify-between gap-2">
            <span class="pc-field-hint m-0">{{ __('Choose Panelless in the form\'s theme to let it sit on the site\'s background.') }}</span>
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm flex-none" onclick="copyToClipboard(document.getElementById('shareFormEmbed'))">
                <i class="fa-regular fa-copy" aria-hidden="true"></i>{{ __('Copy') }}
            </button>
        </div>
    </div>
    <x-slot:footer>
        <a href="{{ $link }}" class="pc-btn pc-btn-secondary" target="_blank" rel="noopener"><i class="fa-regular fa-arrow-up-right-from-square" aria-hidden="true"></i>{{ __('Open form') }}</a>
        <button type="button" class="pc-btn pc-btn-primary" data-pc-dismiss="modal">{{ __('Done') }}</button>
    </x-slot:footer>
</x-dialog.modal>
