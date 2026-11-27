{{-- Why the form can't be filled in: archived, not open yet ($state scheduled) or closed --}}
@php
    $notice = [
        'archived' => ['icon' => 'box-archive', 'title' => __('This form is no longer available'), 'text' => __('It has been taken down by its owner.')],
        'scheduled' => ['icon' => 'calendar', 'title' => __('This form isn\'t open yet'),
            'text' => $form->opens_at ? __('It opens on :date.', ['date' => App\Support\DateFormat::format($form->opens_at, 'datetime')]) : null],
        'closed' => ['icon' => 'lock', 'title' => __('This form is closed'), 'text' => __('It isn\'t taking responses any more.')],
    ][$state];
@endphp

<main class="pc-public-notice">
    <div class="pc-public-notice-card">
        <span class="pc-empty-icon"><i class="fa-regular fa-{{ $notice['icon'] }} text-[22px]" aria-hidden="true"></i></span>
        <h1 class="pc-public-notice-title">{{ $notice['title'] }}</h1>
        @if ($notice['text'])<p>{{ $notice['text'] }}</p>@endif
        <p class="text-xs text-pc-muted">{{ $form->title }}</p>
    </div>
</main>
