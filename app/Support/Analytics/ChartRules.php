<?php

namespace App\Support\Analytics;

/**
 * ChartRules
 *
 * A Visualize chart's rules: cleaned against the form's questions when the chart is saved (clean()), and applied to its
 * summary when the page is drawn (present()): the visual it opens with, the order of its rows and how many it shows.
 *
 *   question chart: {question, visual, sort: form|desc|asc|label, limit: 0 (all) .. MAX_LIMIT}
 *   grouped chart:  {label, mode: numbers|split, series: [{question, aggregate}] (numbers), split (split), visual, sort,
 *                    limit}: per answer of the label question, number questions summed, averaged, counted, or their
 *                    smallest / largest (numbers), or the answers of a second choice question counted (split)
 *
 * Unordered rows (choices, yes / no, file types) that nobody picked are left out of the chart; ordered steps (rating
 * steps, number ranges) keep theirs, so a scale reads without gaps.
 */
class ChartRules
{
    /** Row orders: the form's, most picked first, least picked first, A–Z. */
    const SORTS = ['form', 'desc', 'asc', 'label'];

    /** Most rows a chart can be limited to. */
    const MAX_LIMIT = 50;

    /** Summary kinds whose rows have no order of their own, so they can be re-sorted and empty ones dropped. */
    const UNORDERED = ['choice', 'choices', 'boolean', 'file'];

    /** How a number question is worked out per group. */
    const AGGREGATES = ['sum', 'avg', 'count', 'min', 'max'];

    /** Most number questions one grouped chart compares. */
    const MAX_SERIES = 6;

    /** Kinds of question whose answers can label groups (short text included, so free text like a school's name). */
    const LABEL_KINDS = ['choice', 'choices', 'boolean', 'rating', 'nps', 'number', 'text'];

    /** Kinds of question a grouped chart can split each group by. */
    const SPLIT_KINDS = ['choice', 'choices', 'boolean', 'rating', 'nps'];

    /** Kinds of question a grouped chart works out per group. */
    const SERIES_KINDS = ['number', 'rating', 'nps'];

    /**
     * A grouped chart's rules cleaned against the form's questions, or null when its label, series or split question
     * isn't one it can use.
     *
     * @param  array<string, mixed>  $input
     * @param  list<Question>  $questions
     * @return array{label: string, mode: string, series: list<array{question: string, aggregate: string}>, split: ?string, visual: string, sort: string, limit: int}|null
     */
    public static function cleanGrouped(array $input, array $questions): ?array
    {
        $byName = collect($questions)->keyBy(fn (Question $question) => $question->name());
        $label = $byName->get($input['label'] ?? null);
        if (! $label || ! self::labels($label)) {
            return null;
        }

        $mode = ($input['mode'] ?? null) === 'split' ? 'split' : 'numbers';
        $series = [];
        $split = null;

        if ($mode === 'numbers') {
            foreach (is_array($input['series'] ?? null) ? $input['series'] : [] as $row) {
                $question = is_array($row) ? $byName->get($row['question'] ?? null) : null;
                if (! $question || ! in_array($question->kind, self::SERIES_KINDS, true)) {
                    continue;
                }
                $aggregate = in_array($row['aggregate'] ?? null, self::AGGREGATES, true) ? $row['aggregate'] : 'sum';
                $series[$question->name().':'.$aggregate] = ['question' => $question->name(), 'aggregate' => $aggregate];
            }
            $series = array_slice(array_values($series), 0, self::MAX_SERIES);
            if (! $series) {
                return null;
            }
        } else {
            $question = $byName->get($input['split'] ?? null);
            if (! $question || ! in_array($question->kind, self::SPLIT_KINDS, true) || $question->name() === $label->name()) {
                return null;
            }
            $split = $question->name();
        }

        $visuals = self::groupedVisuals($mode, count($series));

        return [
            'label' => $label->name(),
            'mode' => $mode,
            'series' => $series,
            'split' => $split,
            'visual' => in_array($input['visual'] ?? null, $visuals, true) ? $input['visual'] : $visuals[0],
            'sort' => in_array($input['sort'] ?? null, self::SORTS, true) ? $input['sort'] : 'desc',
            // every group shows (charts saved with a limit included: rules are cleaned again when drawn)
            'limit' => 0,
        ];
    }

    /**
     * The visuals a grouped chart can switch between, its default first: one number per group reads as bars, a line
     * or a donut; several as bars side by side, stacked, 100% stacked or lines; a split as stacked bars by default.
     *
     * @return list<string>
     */
    public static function groupedVisuals(string $mode, int $series): array
    {
        return match (true) {
            $mode === 'split' => ['stacked', 'stacked100', 'bar', 'line', 'table'],
            $series > 1 => ['bar', 'stacked', 'stacked100', 'line', 'table'],
            default => ['bar', 'line', 'donut', 'table'],
        };
    }

    /** Whether a question's answers can label groups (long comments can't). */
    public static function labels(Question $question): bool
    {
        return in_array($question->kind, self::LABEL_KINDS, true) && $question->type() !== 'comment';
    }

    /**
     * The questions a grouped chart's form offers, as name => title: those that can label groups, be worked out per
     * group (series), or split each group.
     *
     * @param  list<Question>  $questions
     * @return array{labels: array<string, string>, series: array<string, string>, splits: array<string, string>}
     */
    public static function groupable(array $questions): array
    {
        $titles = fn (callable $keep) => collect($questions)->filter($keep)
            ->mapWithKeys(fn (Question $question) => [$question->name() => $question->title()])->all();

        return [
            'labels' => $titles(fn (Question $question) => self::labels($question)),
            'series' => $titles(fn (Question $question) => in_array($question->kind, self::SERIES_KINDS, true)),
            'splits' => $titles(fn (Question $question) => in_array($question->kind, self::SPLIT_KINDS, true)),
        ];
    }

    /**
     * A question chart's rules cleaned against the form's questions, or null when the question isn't one that can be
     * charted (missing, free text).
     *
     * @param  array<string, mixed>  $input
     * @param  list<Question>  $questions
     * @return array{question: string, visual: ?string, sort: string, limit: int}|null
     */
    public static function clean(array $input, array $questions): ?array
    {
        $question = collect($questions)->first(fn (Question $question) => $question->name() === ($input['question'] ?? null));
        if (! $question || $question->kind === 'text') {
            return null;
        }

        $visual = $input['visual'] ?? null;
        $limit = is_numeric($input['limit'] ?? null) ? (int) $input['limit'] : 0;

        return [
            'question' => $question->name(),
            'visual' => is_string($visual) && in_array($visual, $question->visuals(), true) ? $visual : null,
            'sort' => in_array($input['sort'] ?? null, self::SORTS, true) ? $input['sort'] : 'form',
            'limit' => max(0, min(self::MAX_LIMIT, $limit)),
        ];
    }

    /**
     * The questions a chart can show, as name => title (free text can't be charted).
     *
     * @param  list<Question>  $questions
     * @return array<string, string>
     */
    public static function chartable(array $questions): array
    {
        return collect($questions)
            ->reject(fn (Question $question) => $question->kind === 'text')
            ->mapWithKeys(fn (Question $question) => [$question->name() => $question->title()])
            ->all();
    }

    /**
     * A question's summary with a chart's rules applied: empty unordered rows dropped, rows sorted and limited (the
     * rest folded into "Other values"), and the chart's visual first among those it can switch to.
     *
     * @param  array<string, mixed>  $summary
     * @param  array{visual?: ?string, sort?: string, limit?: int}  $rules
     * @return array<string, mixed>
     */
    public static function present(array $summary, array $rules): array
    {
        if (in_array($summary['kind'], self::UNORDERED, true) && isset($summary['categories'])) {
            $rows = array_values(array_filter($summary['categories'], fn (array $row) => $row['count'] > 0));

            $rows = match ($rules['sort'] ?? 'form') {
                'desc' => collect($rows)->sortByDesc('count')->values()->all(),
                'asc' => collect($rows)->sortBy('count')->values()->all(),
                'label' => collect($rows)->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
                default => $rows,
            };

            $limit = (int) ($rules['limit'] ?? 0);
            if ($limit > 0 && count($rows) > $limit) {
                $rest = array_slice($rows, $limit - 1);
                $rows = [...array_slice($rows, 0, $limit - 1), [
                    'value' => '',
                    'label' => __('Other values'),
                    'count' => array_sum(array_column($rest, 'count')),
                    'percent' => round(array_sum(array_column($rest, 'percent')), 1),
                    'special' => 'rest',
                ]];
            }

            $summary['categories'] = $rows;
        }

        $visual = $rules['visual'] ?? null;
        if ($visual && in_array($visual, $summary['visuals'], true)) {
            $summary['visuals'] = array_values(array_unique([$visual, ...$summary['visuals']]));
        }

        return Analytics::withHeights($summary);
    }
}
