{{--
    Row delete button: confirms, then posts to `action` through submitForm().
      <x-action.delete :action="route('admin.users.destroy', $user)" confirm="Delete {{ $user->name }}?" text="They lose access straight away." />
--}}
@props(['action', 'confirm', 'text' => __("This can't be undone."), 'label' => __('Delete')])

<form method="POST" action="{{ $action }}" onsubmit="submitForm(event)" class="inline-flex">
    <button type="submit" {{ $attributes->class('pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm text-pc-danger-strong') }} aria-label="{{ $label }}" data-pc-tooltip="{{ __('Delete') }}"
        data-pc-confirm="{{ $confirm }}" data-pc-confirm-text="{{ $text }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
        <i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>
    </button>
</form>
