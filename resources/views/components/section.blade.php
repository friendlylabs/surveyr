{{--
    A page section: a title (and optional description and actions) over its content, with no box around it. Sections
    in a row are divided by a hairline, none above the first. Use it in place of a card for parts of a page; keep
    pc-card for things that stand alone (gallery items, placeholders).
      <x-section id="password" :title="__('Password')" :description="__('Changing it signs out your other devices.')" split>
          <x-slot:actions> <button class="pc-btn pc-btn-secondary pc-btn-sm">…</button> </x-slot:actions>
          …content…
      </x-section>
    split: settings layout; the title and description on the left, the content on the right (stacked below lg).
    Wrap related sections in <div class="pc-section-group"> to drop the lines between them (one line above and below).
    title: text, or a <x-slot:title> holding text plus badges. With an `id`, the heading is "{id}Title" and labels the
    section. Lists inside: <ul class="pc-section-list"> of <li class="pc-section-row">.
--}}
@props(['title', 'description' => null, 'actions' => null, 'split' => false])

@php($headingId = $attributes->get('id') ? $attributes->get('id').'Title' : null)

<section {{ $attributes->class(['pc-section', 'pc-section-split' => $split]) }} @if ($headingId) aria-labelledby="{{ $headingId }}" @endif>
    <header class="pc-section-header">
        <div class="min-w-0">
            <h2 @if ($headingId) id="{{ $headingId }}" @endif class="pc-section-title">{{ $title }}</h2>
            @if ($description)<p class="pc-section-desc">{{ $description }}</p>@endif
        </div>
        @if ($actions)<div class="pc-section-actions">{{ $actions }}</div>@endif
    </header>
    <div class="pc-section-body">
        {{ $slot }}
    </div>
</section>
