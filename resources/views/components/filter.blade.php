{{--
    Toolbar filter: a GET select that reloads the list on change, keeping the page's other query filters.
      <x-filter name="status" :options="['verified' => 'Confirmed', 'unverified' => 'Unconfirmed']" all="Any status" />
    options: [value => label]; `all` is the first option's text (empty value = no filter).
--}}
@props(['name', 'options' => [], 'all' => 'All'])

<form method="GET" {{ $attributes->class('w-44 max-sm:w-full') }}>
    @foreach (request()->except([$name, 'page']) as $key => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <select name="{{ $name }}" class="pc-select" aria-label="{{ $all }}" onchange="this.form.submit()">
        <option value="">{{ $all }}</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(strcasecmp((string) request($name), (string) $value) === 0)>{{ $text }}</option>
        @endforeach
    </select>
</form>
