{{--
    The responses table: the reference (RSP-12) pinned at the start, when it came in (and under it who collected it,
    when staff did), then one column per question, scrolling sideways. Headings sort the list; a row opens the response in the side panel.
--}}
<x-table :paginator="$responses" :label="__('Responses')" :filterable="$hasResponses" :phone-filters="false" class="pc-responses-table">
    {{-- filter order: text / number inputs, then selects --}}
    <x-slot:search>
        <x-search :placeholder="__('Search answers')" />
    </x-slot:search>

    <x-slot:filters>
        {{-- saved views (the Filter button is in the page head); phones do without --}}
        <div class="pc-dropdown">
            <button type="button" class="pc-btn pc-btn-secondary max-w-56" data-pc-trigger="dropdown" data-pc-target="#responseViews" aria-haspopup="menu" aria-expanded="false">
                <span class="truncate">{{ $activeView?->name ?? __('All responses') }}</span><i class="fa-regular fa-chevron-down text-xs" aria-hidden="true"></i>
            </button>
            <div id="responseViews" class="pc-dropdown-menu" role="menu" aria-hidden="true">
                <a href="{{ route('forms.responses', array_filter([$form, 'archived' => $archived ? 1 : null])) }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => ! $activeView]) role="menuitem">{{ __('All responses') }}</a>
                @foreach ($views as $view)
                    <a href="{{ route('forms.responses', array_filter([$form, 'view' => $view->hash, 'archived' => $archived ? 1 : null])) }}" @class(['pc-dropdown-item', 'pc-dropdown-item-active' => $activeView?->is($view)]) role="menuitem">
                        <span class="truncate">{{ $view->name }}</span>
                    </a>
                @endforeach
                <div class="pc-dropdown-divider"></div>
                <a href="{{ route('forms.show', [$form, 'tab' => 'submissions']) }}" class="pc-dropdown-item" role="menuitem">
                    <i class="fa-regular fa-gear text-base" aria-hidden="true"></i>{{ __('Manage views') }}
                </a>
            </div>
        </div>
    </x-slot:filters>

    {{-- as in the legacy app: the exports and Visualize at the end, the bulk pill beside them. Export opens its options
         (partials/export) for what the table lists; Export attachments (forms that take files) zips just the files in the
         background; Visualize summarises the same responses, question by question --}}
    <x-slot:tools>
        <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" data-pc-trigger="modal" data-pc-target="#exportResponses" aria-label="{{ __('Export to Excel') }}" data-pc-tooltip="{{ __('Export to Excel') }}">
            <i class="fa-regular fa-file-excel text-base" aria-hidden="true"></i>
        </button>
        @if ($hasAttachments)
            <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" data-export-attachments-run aria-label="{{ __('Export attachments') }}" data-pc-tooltip="{{ __('Export attachments') }}">
                <i class="fa-regular fa-paperclip text-base" aria-hidden="true"></i>
            </button>
        @endif
        <a href="{{ route('forms.responses.visualize', [$form, ...App\Support\ResponsesQuery::carried(request())]) }}" class="pc-btn pc-btn-secondary pc-btn-icon" aria-label="{{ __('Visualize') }}" data-pc-tooltip="{{ __('Visualize') }}">
            <i class="fa-regular fa-chart-pie text-base" aria-hidden="true"></i>
        </a>
    </x-slot:tools>

    {{-- anyone can export the ticked rows (the export options open for them); those who can edit the form archive or restore them --}}
    <x-slot:bulk>
        <button type="button" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" data-export-ticked>
            <i class="fa-regular fa-file-excel text-base" aria-hidden="true"></i>{{ __('Export selected') }}
        </button>
        @if ($hasAttachments)
            <button type="button" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown" data-export-attachments-run>
                <i class="fa-regular fa-paperclip text-base" aria-hidden="true"></i>{{ __('Export attachments') }}
            </button>
        @endif
        @if ($canArchive)
            <form method="POST" action="{{ route('forms.responses.archive', $form) }}" onsubmit="submitForm(event)" data-pc-bulk-form="responses">
                <input type="hidden" name="archive" value="{{ $archived ? 0 : 1 }}">
                <button type="submit" class="pc-dropdown-item w-full" role="menuitem" data-pc-dismiss="dropdown">
                    @if ($archived)
                        <i class="fa-regular fa-arrow-rotate-left text-base" aria-hidden="true"></i>{{ __('Restore') }}
                    @else
                        <i class="fa-regular fa-box-archive text-base" aria-hidden="true"></i>{{ __('Archive') }}
                    @endif
                </button>
            </form>
        @endif
    </x-slot:bulk>

    <x-slot:head>
        <x-table.sort column="number" :label="__('Response')" :current="$sort" :direction="$direction" class="pc-col-pin" />
        <x-table.sort column="submitted" :label="__('Submitted')" :current="$sort" :direction="$direction" />
        @foreach ($questions as $question)
            @if (App\Http\Controllers\App\FormResponsesController::sortable($question))
                <x-table.sort :column="$question['name']" :label="$question['title']" :current="$sort" :direction="$direction" />
            @else
                <th title="{{ $question['title'] }}">{{ Str::limit($question['title'], 40) }}</th>
            @endif
        @endforeach
    </x-slot:head>

    <x-slot:empty>
        @if (request()->filled('q') || $filterCount)
            <x-empty icon="magnifying-glass" :title="__('No responses match')">
                <div class="max-w-xs">{{ __('Try another search, or clear the filters.') }}</div>
            </x-empty>
        @elseif ($archived)
            <x-empty icon="box-archive" :title="__('No archived responses')">
                <div class="max-w-xs">{{ __('Responses you archive wait here, out of the list, until you restore them.') }}</div>
            </x-empty>
        @else
            <x-empty icon="inbox" :title="__('No responses yet')">
                <div class="max-w-xs">{{ __('Share the form and responses show up here as they come in.') }}</div>
            </x-empty>
        @endif
    </x-slot:empty>

    @foreach ($responses as $response)
        <tr class="cursor-pointer" data-pc-response="{{ $response->hash }}">
            <x-table.select :value="$response->hash" :label="$response->reference" />
            <td class="pc-col-pin">
                <button type="button" class="pc-response-ref" data-pc-response-open="{{ $response->hash }}" aria-label="{{ __('Open :reference', ['reference' => $response->reference]) }}">
                    <span class="block font-mono text-[13px] font-bold text-pc-ink">{{ $response->reference }}</span>
                    <span class="block text-xs whitespace-nowrap text-pc-muted">{{ $response->created_at->diffForHumans() }}</span>
                </button>
            </td>
            {{-- when it came in; under it the staff member who filled it in, when one did --}}
            <td class="whitespace-nowrap">
                <span class="block font-mono text-xs text-pc-text-2">{{ $response->created_at->format('j M Y, H:i') }}</span>
                @if ($response->collector)
                    <span class="block text-xs text-pc-muted">{{ __('by :name', ['name' => $response->collector->name]) }}</span>
                @endif
            </td>
            @foreach ($questions as $question)
                @php($answer = App\Support\SurveyAnswers::cell($response->data[$question['name']] ?? null, $question))
                <td @class(['max-w-60 text-[13px] text-pc-text-2', 'font-mono' => in_array($question['kind'], ['rating', 'number'], true), 'font-semibold text-pc-ink' => $question['kind'] === 'rating'])>
                    @if ($answer !== '')<span class="block truncate" title="{{ $answer }}">{{ $answer }}</span>@else<span class="text-pc-muted">—</span>@endif
                </td>
            @endforeach
        </tr>
    @endforeach
</x-table>
