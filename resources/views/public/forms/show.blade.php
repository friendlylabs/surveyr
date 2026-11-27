{{--
    A form as respondents reach it (Public\FormsController@show): drawn by SurveyJS in its theme while it's open; else
    the access code step, or a notice saying why it can't be filled in. A panelless form leaves the page clear, so where
    it's embedded the host page shows through.
--}}
{{-- the open form brings its own colours (its theme), so it ignores the visitor's light / dark choice --}}
<x-layout-public :title="$form->title" :clear="$state === 'open' && ($form->theme['layout'] ?? null) === 'panelless'" :follow-theme="$state !== 'open'">
    @if ($state === 'open')
        @include('public.forms.partials.runner')
    @elseif ($state === 'locked')
        @include('public.forms.partials.unlock')
    @else
        @include('public.forms.partials.notice')
    @endif
</x-layout-public>
