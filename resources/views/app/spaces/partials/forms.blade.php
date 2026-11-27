{{-- The space's Forms tab: the same list as the forms page, for this space's forms (Clear filters stays on this tab) --}}
@include('app.forms.partials.table', [
    'clearUrl' => route('spaces.show', [$space, 'tab' => 'forms']),
    'emptyTitle' => __('No forms in this space yet'),
    'emptyText' => __('Add a form to this space when you create it, or from its settings.'),
    // every row is in this space, so the column would only repeat its name
    'showSpaces' => false,
])
