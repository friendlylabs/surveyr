{{--
    Page loader: a little form assembly line. Blank forms ride in on a belt, get filled at the first station
    (a stream of answers pours in, fields tick in like keystrokes), get a check as they reach the next stop, and ride out.
    One loop = one form; the belt moves one pitch per loop, so the scene repeats seamlessly (app.css, "Page loader").

    Layouts render it once, first thing in <body>: <x-page-loader />. pc-loader.js hides it when the page is ready
    and brings it back on navigation. It only fades in after a short delay, so fast pages never show it.
    `overlay` false renders the scene inline (showcases, panels that load their own content).
--}}
@props(['overlay' => true, 'message' => __('Getting things ready…')])

<div {{ $attributes->class(['pc-loader', 'pc-page-loader' => $overlay]) }}
    @if ($overlay) data-pc-component="page-loader" data-pc-state="loading" @endif
    role="status" aria-live="polite">
    <svg class="pc-loader-scene" viewBox="0 0 216 108" aria-hidden="true" focusable="false">
        {{-- soft disc behind the stop where the check lands --}}
        <circle class="pc-loader-disc" cx="149" cy="62" r="34"/>

        {{-- belt: rollers drift by one pitch per loop --}}
        <line class="pc-loader-belt" x1="-20" y1="95" x2="236" y2="95"/>
        <line class="pc-loader-rollers" x1="-20" y1="99" x2="236" y2="99"/>

        {{-- answer stream at the fill station --}}
        <line class="pc-loader-stream" x1="81" y1="-2" x2="81" y2="47" pathLength="100"/>

        <g class="pc-loader-line">
            @foreach ([-27 => 'blank', 27 => 'blank', 81 => 'fill', 135 => 'seal', 189 => 'done'] as $x => $step)
                <g transform="translate({{ $x }} 0)">
                    <g class="pc-loader-card" data-step="{{ $step }}">
                        <rect class="pc-loader-sheet" x="-17" y="48" width="34" height="44" rx="4"/>
                        <rect class="pc-loader-edge" x="-17" y="48" width="34" height="44" rx="4" pathLength="100"/>
                        <rect class="pc-loader-title" x="-11" y="55" width="13" height="3" rx="1.5"/>
                        @foreach ([64, 72, 80] as $row => $y)
                            <circle class="pc-loader-choice" cx="-9" cy="{{ $y + 1.5 }}" r="2.2"/>
                            <circle class="pc-loader-dot" cx="-9" cy="{{ $y + 1.5 }}" r="2.2" data-row="{{ $row }}"/>
                            <rect class="pc-loader-field" x="-4" y="{{ $y }}" width="15" height="3" rx="1.5"/>
                            <rect class="pc-loader-answer" x="-4" y="{{ $y }}" width="15" height="3" rx="1.5" data-row="{{ $row }}"/>
                        @endforeach
                        <g transform="translate(14 50)">
                            <g class="pc-loader-seal">
                                <circle r="6"/>
                                <path d="m-2.6 0 1.8 1.8 3.4-3.6"/>
                            </g>
                        </g>
                    </g>
                </g>
            @endforeach
        </g>
    </svg>

    <div class="pc-loader-progress"><span></span></div>
    <div class="pc-loader-message">{{ $message }}</div>
    @if ($overlay)<noscript><style>.pc-page-loader{display:none}</style></noscript>@endif
</div>
