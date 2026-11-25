{{--
    Labelled textarea with its hint and error box (error id = "{id}Error").
      <x-form.textarea id="addDescription" name="description" label="Description" rows="3" />
--}}
@props(['id', 'name', 'label', 'value' => null, 'hint' => null])

<div>
    <label @class(['pc-label', 'required' => $attributes->has('required')]) for="{{ $id }}">{{ $label }}</label>
    <textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes->class('pc-textarea') }}
        aria-describedby="{{ $hint ? "{$id}Hint " : '' }}{{ $id }}Error">{{ $value }}</textarea>
    @if ($hint)<div id="{{ $id }}Hint" class="pc-field-hint">{{ $hint }}</div>@endif
    <div id="{{ $id }}Error" class="pc-field-error" hidden></div>
</div>
