{{--
    Labelled select with its hint and error box (error id = "{id}Error"); pc-select.js turns it into the
    searchable flyout select, single or multiple.
      <x-form.select id="addRole" name="role_id" label="Role" :options="$roles" placeholder="Choose a role" />
      <x-form.select id="addTeams" name="teams" label="Teams" :options="$teams" multiple placeholder="Choose teams" />
    options: [value => label], or [group label => [value => label]] for grouped options.
    `selected` preselects a value (an array when multiple); multiple posts as name[].
    search: true / false forces the search box (default: shown when there are more than 7 options).
--}}
@props(['id', 'name', 'label', 'options' => [], 'selected' => null, 'placeholder' => null, 'hint' => null, 'multiple' => false, 'search' => null])

@php
    $chosen = array_map('strval', array_filter((array) $selected, fn ($v) => $v !== null));
    $isChosen = fn ($value) => in_array((string) $value, $chosen, true);
@endphp

<div>
    <label @class(['pc-label', 'required' => $attributes->has('required')]) for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $multiple ? "{$name}[]" : $name }}" {{ $attributes->class('pc-select') }}
        @if ($multiple) multiple @if ($placeholder) data-placeholder="{{ $placeholder }}" @endif @endif
        @if ($search !== null) data-search="{{ $search ? 'true' : 'false' }}" @endif
        aria-describedby="{{ $hint ? "{$id}Hint " : '' }}{{ $id }}Error">
        @if ($placeholder && ! $multiple)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)
            @if (is_iterable($text))
                <optgroup label="{{ $value }}">
                    @foreach ($text as $groupValue => $groupText)
                        <option value="{{ $groupValue }}" @selected($isChosen($groupValue))>{{ $groupText }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $value }}" @selected($isChosen($value))>{{ $text }}</option>
            @endif
        @endforeach
    </select>
    @if ($hint)<div id="{{ $id }}Hint" class="pc-field-hint">{{ $hint }}</div>@endif
    <div id="{{ $id }}Error" class="pc-field-error" hidden></div>
</div>
