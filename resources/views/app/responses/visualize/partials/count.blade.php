{{--
    A question summarised by its count alone: signatures (how many signed; the drawings stay in the responses) and
    types the visualizer doesn't break down yet.
--}}
@include('app.responses.visualize.partials.stats', ['figures' => [
    [number_format($summary['answered']), $summary['kind'] === 'signature' ? __('Signed') : __('Answered')],
    [number_format($summary['skipped']), $summary['kind'] === 'signature' ? __('Not signed') : __('Skipped')],
]])
@if ($summary['kind'] === 'unsupported')
    <p class="pc-viz-note">{{ __('A breakdown of this kind of question isn\'t available yet; open the answers in the responses table.') }}</p>
@endif
