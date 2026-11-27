{{-- Forms (FormsController@index): the forms the user can see; search and status filters --}}
<x-layout-app :title="__('Forms')">
    <x-slot:header>
        <div class="ms-auto flex items-center gap-2">
            <button type="button" class="pc-btn pc-btn-primary" data-pc-trigger="modal" data-pc-target="#addForm">
                <i class="fa-regular fa-plus" aria-hidden="true"></i><span class="pc-btn-label">{{ __('New form') }}</span>
            </button>
        </div>
    </x-slot:header>

    @include('app.forms.partials.table')

    @include('app.forms.partials.add')
</x-layout-app>
