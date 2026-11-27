{{-- name, description and colour (each colour previewed as a swatch, as its tiles show it): shared by the create and edit
    dialogs and the Settings tab; $prefix keeps the field ids apart --}}
@php
    $colorOptions = collect($colors)->mapWithKeys(fn ($color) => [$color => __(ucfirst($color))])->all();
    // ink tiles use the strong fill colour (the ink token turns light in dark mode)
    $colorSwatches = collect($colors)
        ->mapWithKeys(fn ($color) => [$color => $color === 'ink' ? 'var(--color-pc-fill)' : 'var(--color-pc-'.$color.')'])
        ->put('', 'none')
        ->all();
@endphp
<x-form.input id="{{ $prefix }}Name" name="name" :label="__('Name')" :value="$space?->name" required autocomplete="off" data-pc-autofocus />
<x-form.input id="{{ $prefix }}Description" name="description" :label="__('Description')" :value="$space?->description" autocomplete="off" />
<x-form.select id="{{ $prefix }}Color" name="color" :label="__('Colour')" :selected="$space?->color"
    :options="$colorOptions" :placeholder="__('No colour')" :swatches="$colorSwatches" />
