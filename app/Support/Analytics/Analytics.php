<?php

namespace App\Support\Analytics;

use App\Models\Form;
use App\Models\Submission;
use App\Models\VisualizationChart;
use App\Support\Analytics\Summaries\BooleanSummary;
use App\Support\Analytics\Summaries\ChoiceSummary;
use App\Support\Analytics\Summaries\FileSummary;
use App\Support\Analytics\Summaries\GroupedSummary;
use App\Support\Analytics\Summaries\MatrixSummary;
use App\Support\Analytics\Summaries\NumberSummary;
use App\Support\Analytics\Summaries\RankingSummary;
use App\Support\Analytics\Summaries\RatingSummary;
use App\Support\Analytics\Summaries\SignatureSummary;
use App\Support\Analytics\Summaries\Summary;
use App\Support\Analytics\Summaries\UnsupportedSummary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Analytics
 *
 * The visualizer's counting, done on the server so the page only ever gets small summaries: one pass over the
 * responses a query lists (in chunks, so memory stays flat however many there are), the Summary of every question a
 * tab's charts show fed from each. Free text isn't charted; a choice question's typed Other texts are listed a page at
 * a time (answers()).
 */
class Analytics
{
    /** Responses read per chunk. */
    const CHUNK = 1000;

    /** Other texts per page. */
    const PER_PAGE = 10;

    /** Summary class per question kind. */
    const SUMMARIES = [
        'choice' => ChoiceSummary::class,
        'choices' => ChoiceSummary::class,
        'boolean' => BooleanSummary::class,
        'rating' => RatingSummary::class,
        'nps' => RatingSummary::class,
        'number' => NumberSummary::class,
        'ranking' => RankingSummary::class,
        'matrix' => MatrixSummary::class,
        'file' => FileSummary::class,
        'signature' => SignatureSummary::class,
        'unsupported' => UnsupportedSummary::class,
    ];

    /**
     * A card for each chart over the responses the query lists, in the charts' order, named by the chart's hash: a
     * question chart's question summary with its rules applied (ChartRules::present; charts on the same question share
     * one summary), or a grouped chart's GroupedSummary. A chart whose questions are gone from the form, or can no
     * longer be used, gets a "missing" card.
     *
     * @param  iterable<VisualizationChart>  $charts
     * @return list<array<string, mixed>>
     */
    public static function summarise(Form $form, Builder $query, iterable $charts): array
    {
        $questions = Questions::from($form->content);
        $all = collect($questions)->keyBy(fn (Question $question) => $question->name());
        $byName = $all->reject(fn (Question $question) => $question->kind === 'text');
        $charts = collect($charts);

        // question charts by question, grouped charts by chart
        $summaries = collect();
        foreach ($charts as $chart) {
            if ($chart->kind === 'grouped') {
                $rules = ChartRules::cleanGrouped($chart->rules, $questions);
                if ($rules) {
                    $summaries['grouped:'.$chart->hash] = new GroupedSummary(
                        $all[$rules['label']],
                        $rules,
                        array_map(fn (array $series) => $all[$series['question']], $rules['series']),
                        $rules['split'] ? $all[$rules['split']] : null,
                    );
                }
            } elseif (is_string($name = $chart->rules['question'] ?? null) && $byName->has($name) && ! $summaries->has('question:'.$name)) {
                $summaries['question:'.$name] = self::summary($byName[$name]);
            }
        }

        if ($summaries->isNotEmpty()) {
            // the raw rows, not models: decoding the answers is all that's needed
            $query->reorder()->select(['id', 'hash', 'number', 'data'])->toBase()->lazyById(self::CHUNK, 'id')
                ->each(function (object $row) use ($summaries) {
                    $data = is_string($row->data) ? json_decode($row->data, true) : null;
                    $data = is_array($data) ? $data : [];
                    $response = ['hash' => (string) $row->hash, 'reference' => 'RSP-'.$row->number];
                    foreach ($summaries as $summary) {
                        $summary->add($data, $response);
                    }
                });
        }
        $counted = $summaries->map(fn (Summary $summary) => $summary->toArray());

        return $charts->map(function (VisualizationChart $chart) use ($counted, $all) {
            $question = $chart->rules['question'] ?? null;
            $summary = match (true) {
                $chart->kind === 'grouped' && isset($counted['grouped:'.$chart->hash]) => [...$counted['grouped:'.$chart->hash], 'title' => self::groupedTitle($chart->rules, $all)],
                $chart->kind !== 'grouped' && isset($counted['question:'.$question]) => ChartRules::present($counted['question:'.$question], $chart->rules),
                default => self::withHeights(['kind' => 'missing', 'visuals' => ['stats'], 'title' => (string) ($question ?? $chart->rules['label'] ?? ''), 'type' => '', 'total' => 0, 'answered' => 0, 'skipped' => 0, 'unreadable' => 0, 'percent' => 0.0]),
            };

            return [
                ...$summary,
                'name' => $chart->hash,
                'question' => $question ?? ($chart->rules['label'] ?? null),
                'title' => $chart->title ?: $summary['title'],
            ];
        })->values()->all();
    }

    /**
     * A grouped chart's title unless it has its own: what it works out "by" its label, e.g. "Computers, Printers by
     * School name" or "Internet access by School type".
     *
     * @param  array<string, mixed>  $rules
     * @param  Collection<string, Question>  $questions
     */
    private static function groupedTitle(array $rules, $questions): string
    {
        $title = fn (?string $name) => $questions->get($name)?->title() ?? (string) $name;
        $what = ($rules['mode'] ?? 'numbers') === 'split'
            ? $title($rules['split'] ?? null)
            : collect($rules['series'] ?? [])->map(fn (array $series) => $title($series['question'] ?? null))->unique()->implode(', ');

        return __(':what by :label', ['what' => $what, 'label' => $title($rules['label'] ?? null)]);
    }

    /**
     * The summary with a fixed chart height per visual, in pixels: set here, never measured, so a chart is the same
     * size on screen and on paper.
     *
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public static function withHeights(array $summary): array
    {
        $rows = $summary['kind'] === 'ranking'
            ? count(array_filter($summary['ranking'], fn (array $row) => $row['average'] !== null))
            : count($summary['categories'] ?? $summary['rows'] ?? []);

        $summary['heights'] = [
            'bar' => max(112, 30 * $rows + 52),
            'line' => 260,
            'donut' => max(260, 24 * $rows + 48),
            'nps' => 132,
            'stacked' => max(140, 40 * $rows + 90),
        ];

        return $summary;
    }

    /** The question's summary, by its kind. */
    public static function summary(Question $question): Summary
    {
        $class = self::SUMMARIES[$question->kind];

        return new $class($question);
    }

    /**
     * A page of a choice question's Other texts, newest first: the comment box's, or the text kept as the answer
     * itself (Question::otherTexts), so they're found in one pass over the responses rather than by a JSON path.
     * Each item is a response with its texts joined.
     *
     * @return LengthAwarePaginator<int, array{response: Submission, text: string}>
     */
    public static function answers(Question $question, Builder $query, int $page = 1): LengthAwarePaginator
    {
        $offset = (max(1, $page) - 1) * self::PER_PAGE;
        $items = [];
        $total = 0;

        $query->reorder()->select(['id', 'hash', 'number', 'data', 'created_at'])->lazyByIdDesc(self::CHUNK)
            ->each(function (Submission $response) use ($question, $offset, &$items, &$total) {
                $texts = $question->otherTexts(is_array($response->data) ? $response->data : []);
                if (! $texts) {
                    return;
                }
                if ($total >= $offset && count($items) < self::PER_PAGE) {
                    $items[] = ['response' => $response, 'text' => implode('; ', $texts)];
                }
                $total++;
            });

        return new Paginator($items, $total, self::PER_PAGE, max(1, $page));
    }
}
