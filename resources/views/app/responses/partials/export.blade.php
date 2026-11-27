{{--
    Export options (pages/response-export.js): whether rows start with the response's details, and (for a form that takes
    files) whether its attachments come too, zipped in the background and emailed. Opened from the toolbar (what the
    table lists) or the bulk menu (the ticked rows, sent as responses[]). Below it, the attachments-only export's form,
    sent by its own buttons.
--}}
<x-dialog.modal id="exportResponses" :title="__('Export to Excel')" :subtitle="__('All the responses listed')" :action="route('forms.responses.export', $form)" :submit="__('Export')"
    data-export-form data-export-all="{{ __('All the responses listed') }}" data-export-selected="{{ __(':count selected responses') }}" data-export-selected-one="{{ __('1 selected response') }}">
    <div class="pc-options">
        <label class="pc-option">
            <input type="checkbox" class="pc-select-box" name="details" value="1" checked>
            <span>
                <span class="pc-option-title">{{ __('Metadata details') }}</span>
                <span class="pc-option-hint">{{ __('The response number, when it was submitted, who collected it and from where. Off: only the answers.') }}</span>
            </span>
        </label>
        @if ($hasAttachments)
            <label class="pc-option">
                <input type="checkbox" class="pc-select-box" name="attachments" value="1" data-export-attachments>
                <span>
                    <span class="pc-option-title">{{ __('Attachments') }}</span>
                    <span class="pc-option-hint">{{ __('Uploaded files zipped with the workbook, its links opening them. Prepared in the background; we email you a download link.') }}</span>
                </span>
            </label>
        @endif
    </div>
    <div data-export-selection></div>
</x-dialog.modal>

@if ($hasAttachments)
    {{-- just the attachments (FormResponsesController@attachments): sent by the toolbar and bulk buttons, filled in by the script --}}
    <form method="POST" action="{{ route('forms.responses.attachments', $form) }}" hidden data-export-attachments-form>
        @csrf
    </form>
@endif
