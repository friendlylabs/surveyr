{{--
    Labelled input with its hint and error box, wired for submitForm()/fieldError() (error id = "{id}Error").
      <x-form.input id="addEmail" name="email" type="email" label="Email" required />
    Extra attributes (placeholder, autocomplete, data-*) land on the <input>.
--}}
@props(['id', 'name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

<div>
    <label @class(['pc-label', 'required' => $attributes->has('required')]) for="{{ $id }}">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
        {{ $attributes->class('pc-input') }}
        aria-describedby="{{ $hint ? "{$id}Hint " : '' }}{{ $id }}Error">
    @if ($hint)<div id="{{ $id }}Hint" class="pc-field-hint">{{ $hint }}</div>@endif
    <div id="{{ $id }}Error" class="pc-field-error" hidden></div>
</div>
