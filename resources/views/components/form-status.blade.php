{{-- A form's status from its schedule (Form::status()): Open, Scheduled, Closed or Archived, as a dot and text (pc-status) --}}
@props(['form'])

@php
    $status = $form->status();
    $tones = ['open' => 'success', 'scheduled' => 'info', 'closed' => 'muted', 'archived' => 'muted'];
    $labels = ['open' => __('Open'), 'scheduled' => __('Scheduled'), 'closed' => __('Closed'), 'archived' => __('Archived')];
@endphp

<span {{ $attributes->class(['pc-status', 'pc-status-'.$tones[$status]]) }}><span class="pc-dot pc-dot-sm"></span>{{ $labels[$status] }}</span>
