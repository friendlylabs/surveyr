{{--
    One response in a side panel (pages/responses.js fills it from FormResponsesController@show): its reference and
    when it came in, the answers read-only in the form, stepping to the newer / older one, and archiving (or restoring)
    it.
--}}
<x-dialog.offcanvas id="responsePanel" size="lg" :title="__('Response')" subtitle="">
    <div id="responseView" class="pc-response-view" aria-live="polite"></div>

    <x-slot:footer>
        <div class="flex items-center">
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon rounded-e-none" data-response-step="newer" aria-label="{{ __('Newer response') }}" data-pc-tooltip="{{ __('Newer') }}">
                <i class="fa-regular fa-chevron-left rtl:-scale-x-100" aria-hidden="true"></i>
            </button>
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon -ms-px rounded-s-none" data-response-step="older" aria-label="{{ __('Older response') }}" data-pc-tooltip="{{ __('Older') }}">
                <i class="fa-regular fa-chevron-right rtl:-scale-x-100" aria-hidden="true"></i>
            </button>
        </div>
        <div class="ms-auto flex items-center gap-2">
            @if ($canArchive)
                <form method="POST" action="{{ route('forms.responses.archive', $form) }}" onsubmit="submitForm(event)" data-response-archive>
                    <input type="hidden" name="responses[]" value="">
                    <input type="hidden" name="archive" value="1">
                    <button type="submit" class="pc-btn pc-btn-warning" data-archive-button>
                        <i class="fa-regular fa-box-archive" aria-hidden="true" data-archive-icon></i><span data-archive-label>{{ __('Archive') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </x-slot:footer>
</x-dialog.offcanvas>
