{{--
    Labelled group of checkboxes posting as name[] (error id = "{id}Error").
      <x-form.checklist id="addDepartments" name="departments" label="Departments" :options="$options" />
    options: [value => label]; `selected` = values ticked on render.
--}}
@props(['id', 'name', 'label', 'options' => [], 'selected' => [], 'hint' => null, 'empty' => __('Nothing to choose from yet.')])

<fieldset {{ $attributes }} aria-describedby="{{ $hint ? "{$id}Hint " : '' }}{{ $id }}Error">
    <legend class="pc-label">{{ $label }}</legend>
    @if (count($options))
        <div id="{{ $id }}" class="flex max-h-44 flex-col gap-2 overflow-y-auto rounded-pc-control border border-pc-border px-3 py-2.5">
            @foreach ($options as $value => $text)
                <label class="pc-check text-[13px]">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked(in_array($value, $selected))> {{ $text }}
                </label>
            @endforeach
        </div>
    @else
        <div class="text-[13px] text-pc-muted">{{ $empty }}</div>
    @endif
    @if ($hint)<div id="{{ $id }}Hint" class="pc-field-hint">{{ $hint }}</div>@endif
    <div id="{{ $id }}Error" class="pc-field-error" hidden></div>
</fieldset>
