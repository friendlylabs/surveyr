{{--
    Floating side panel (pc-offcanvas.js), for record details and edits that shouldn't take the reader off the page:
    inset from the screen edges, with expand (wider panel) and close at the top right above a large title.
    Same contract as <x-dialog.modal>: with `action` the panel is a form posted by submitForm().
    Slots: default = body, `footer` replaces the Close / submit buttons. size = lg for the wider panel.
--}}
@props(['id', 'title', 'subtitle' => null, 'size' => null, 'action' => null, 'submit' => 'Save', 'footer' => null])

@php($panelTag = $action ? 'form' : 'div')

<div id="{{ $id }}" {{ $attributes->class(['pc-offcanvas', "pc-offcanvas-$size" => $size]) }} role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="{{ $id }}Title">
    <div class="pc-offcanvas-backdrop" data-pc-dismiss="offcanvas"></div>
    <{{ $panelTag }} class="pc-offcanvas-panel" @if ($action) method="POST" action="{{ $action }}" onsubmit="submitForm(event)" novalidate @endif>
        @if ($action) @csrf @endif
        <div class="pc-offcanvas-header">
            <div class="pc-offcanvas-tools">
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm pc-offcanvas-expand max-sm:hidden" data-pc-offcanvas-expand aria-pressed="false" aria-label="Expand panel">
                    <i class="ph ph-arrows-out-simple pc-offcanvas-expand-icon text-base" aria-hidden="true"></i>
                    <i class="ph ph-arrows-in-simple pc-offcanvas-shrink-icon text-base" aria-hidden="true"></i>
                </button>
                <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" data-pc-dismiss="offcanvas" aria-label="Close">
                    <i class="ph ph-x text-base" aria-hidden="true"></i>
                </button>
            </div>
            <div id="{{ $id }}Title" class="pc-offcanvas-title" data-title>{{ $title }}</div>
            @if ($subtitle !== null)<div class="pc-offcanvas-subtitle" data-subtitle>{{ $subtitle }}</div>@endif
        </div>
        <div class="pc-offcanvas-body flex flex-col gap-5">
            {{ $slot }}
        </div>
        <div class="pc-offcanvas-footer">
            @if ($footer)
                {{ $footer }}
            @else
                <button type="button" class="pc-btn pc-btn-secondary" data-pc-dismiss="offcanvas">Close</button>
                @if ($action)<button type="submit" class="pc-btn pc-btn-primary">{{ $submit }}</button>@endif
            @endif
        </div>
    </{{ $panelTag }}>
</div>
