{{-- The space's Settings tab (Managers): its name, description and colour (SpacesController@update), and deleting it --}}
<form method="POST" action="{{ route('spaces.update', $space) }}" onsubmit="submitForm(event)" novalidate>
    <x-section id="spaceDetails" :title="__('Details')" :description="__('How the space shows in lists and on its page.')" split>
        <div class="flex max-w-100 flex-col gap-4">
            @include('app.spaces.partials.form-fields', ['prefix' => 'spaceSettings', 'space' => $space])
        </div>
    </x-section>

    <div class="pc-settings-actions">
        <button type="submit" class="pc-btn pc-btn-primary">{{ __('Save changes') }}</button>
    </div>
</form>

<x-section id="spaceDelete" class="mt-7 border-t border-pc-border-soft" :title="__('Delete space')" :description="__('Its forms stay; they just leave this space. Its members lose access through it.')" split>
    <form method="POST" action="{{ route('spaces.destroy', $space) }}" onsubmit="submitForm(event)">
        <button type="submit" class="pc-btn pc-btn-soft-danger"
            data-pc-confirm="{{ __('Delete :name?', ['name' => $space->name]) }}" data-pc-confirm-text="{{ __('Its forms stay; they just leave this space.') }}" data-pc-confirm-icon="danger" data-pc-confirm-button="{{ __('Delete') }}">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>{{ __('Delete space') }}
        </button>
    </form>
</x-section>
