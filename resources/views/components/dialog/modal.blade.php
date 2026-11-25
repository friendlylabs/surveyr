{{--
    Modal (pc-modal.js). With `action`, the panel is a form posted by submitForm() — the default for short add / edit
    forms; keep <x-dialog.offcanvas> for long forms and record detail views.
      <x-dialog.modal id="addUser" title="Add user" :action="route('admin.users.store')" submit="Add user">
          fields…
      </x-dialog.modal>
    Slots: default = body, `footer` replaces the Cancel / submit buttons. size = sm | lg | xl.
    Open with data-pc-trigger="modal" data-pc-target="#id" or pcModal.open('id').
--}}
@props(['id', 'title', 'subtitle' => null, 'size' => null, 'action' => null, 'submit' => 'Save', 'footer' => null])

@php($panelTag = $action ? 'form' : 'div')

<div id="{{ $id }}" {{ $attributes->class(['pc-modal', "pc-modal-$size" => $size]) }} role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="{{ $id }}Title">
    <div class="pc-modal-backdrop" data-pc-dismiss="modal"></div>
    <{{ $panelTag }} class="pc-modal-panel" @if ($action) method="POST" action="{{ $action }}" onsubmit="submitForm(event)" novalidate @endif>
        @if ($action) @csrf @endif
        <div class="pc-modal-header">
            <div>
                <div id="{{ $id }}Title" class="pc-modal-title" data-title>{{ $title }}</div>
                @if ($subtitle !== null)<div class="pc-modal-subtitle" data-subtitle>{{ $subtitle }}</div>@endif
            </div>
            <button type="button" class="pc-close" data-pc-dismiss="modal" aria-label="Close">×</button>
        </div>
        <div class="pc-modal-body flex flex-col gap-4">
            {{ $slot }}
        </div>
        <div class="pc-modal-footer">
            @if ($footer)
                {{ $footer }}
            @else
                <button type="button" class="pc-btn pc-btn-secondary" data-pc-dismiss="modal">Cancel</button>
                @if ($action)<button type="submit" class="pc-btn pc-btn-primary">{{ $submit }}</button>@endif
            @endif
        </div>
    </{{ $panelTag }}>
</div>
