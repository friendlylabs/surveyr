{{--
    Row edit button: fills the page's shared edit modal from `payload` and opens it (see app.js [data-pc-edit]).
      <x-action.edit modal="#editUser" :payload="['action' => ..., 'title' => ..., 'fields' => [...]]" label="Edit {{ $user->name }}" />
--}}
@props(['modal', 'payload', 'label' => 'Edit'])

<button type="button" {{ $attributes->class('pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm') }} aria-label="{{ $label }}" data-pc-tooltip="Edit"
    data-pc-edit="{{ $modal }}" data-pc-payload="{{ json_encode($payload) }}">
    <i class="ph ph-pencil-simple text-base" aria-hidden="true"></i>
</button>
