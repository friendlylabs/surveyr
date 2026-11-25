{{--
    Labelled date, time or date-and-time field, for one value or a range, with its hint and error box (error id = "{id}Error");
    pc-date.js turns it into the picker. Values post in DateFormat::zone() as Y-m-d, H:i or Y-m-d H:i; validate them
    with `date_format:` and read them back with DateFormat::parse().
      <x-form.date id="paidOn" name="paid_on" label="Paid on" :value="$invoice->paid_on" required />
      <x-form.date id="opensAt" name="opens_at" label="Opens at" mode="time" />
      <x-form.date id="startsAt" name="starts_at" label="Starts" mode="datetime" :min="now()" />
      <x-form.date id="period" name="from" end-name="to" label="Period" range :value="$from" :end-value="$to" />
    mode: date | time | datetime. range: two values posted as `name` and `end-name` (default "{name}_end"); both share
    the one error box. value / end-value / min / max: Carbon or strings in the posted shape.
    format: how the field shows the value, a DateFormat name or PHP format (default: the mode's name).
    presets: date range shortcuts; true for the standard ones (DateFormat::rangePresets), false for none, or [label => [first, last]].
    step: minutes between the times offered (default 5). zone: the time zone values are in (default DateFormat::zone()).
    required and disabled reach the inputs; other attributes (data-*, class) land on the field.
--}}
@use('App\Support\DateFormat')
@props(['id', 'name', 'label', 'mode' => 'date', 'range' => false, 'endName' => null, 'value' => null, 'endValue' => null,
    'min' => null, 'max' => null, 'format' => null, 'placeholder' => null, 'presets' => true, 'step' => 5, 'zone' => null, 'hint' => null])

@php
    $zone ??= DateFormat::zone();
    $endName ??= "{$name}_end";
    $isRequired = (bool) $attributes->get('required');
    $isDisabled = (bool) $attributes->get('disabled');
    $toInput = fn ($value) => DateFormat::input($value, $mode, $zone);
    $placeholder ??= match ($mode) {
        'time' => $range ? __('Choose times') : __('Choose a time'),
        'datetime' => $range ? __('Choose start and end') : __('Choose date and time'),
        default => $range ? __('Choose dates') : __('Choose a date'),
    };
    $shape = ['date' => 'YYYY-MM-DD', 'time' => 'HH:MM', 'datetime' => 'YYYY-MM-DD HH:MM'][$mode];
    $shortcuts = $range && $mode !== 'time' ? ($presets === true ? DateFormat::rangePresets($zone) : ($presets ?: [])) : [];
    $describedBy = ($hint ? "{$id}Hint " : '')."{$id}Error";
@endphp

<div>
    <label @class(['pc-label', 'required' => $isRequired]) for="{{ $id }}">{{ $label }}</label>
    <div {{ $attributes->except(['required', 'disabled'])->class('pc-date') }} data-pc-date="{{ $mode }}" @if ($range) data-range @endif
        data-display="{{ DateFormat::pattern($format ?? $mode) }}" data-zone="{{ $zone }}" data-step="{{ $step }}" data-placeholder="{{ $placeholder }}"
        @if ($min !== null) data-min="{{ $toInput($min) }}" @endif
        @if ($max !== null) data-max="{{ $toInput($max) }}" @endif
        @if ($shortcuts) data-presets="{{ json_encode($shortcuts) }}" @endif>
        <input id="{{ $id }}" name="{{ $name }}" type="text" value="{{ $toInput($value) }}" class="pc-input" placeholder="{{ $shape }}" autocomplete="off"
            @required($isRequired) @disabled($isDisabled) aria-describedby="{{ $describedBy }}">
        @if ($range)
            <input id="{{ $id }}End" name="{{ $endName }}" type="text" value="{{ $toInput($endValue) }}" class="pc-input" placeholder="{{ $shape }}" autocomplete="off"
                @required($isRequired) @disabled($isDisabled) aria-label="{{ $label }} ({{ __('end') }})" aria-describedby="{{ $describedBy }}">
        @endif
    </div>
    @if ($hint)<div id="{{ $id }}Hint" class="pc-field-hint">{{ $hint }}</div>@endif
    <div id="{{ $id }}Error" class="pc-field-error" hidden></div>
</div>
