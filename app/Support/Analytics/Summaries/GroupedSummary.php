<?php

namespace App\Support\Analytics\Summaries;

use App\Support\Analytics\ChartRules;
use App\Support\Analytics\Question;

/**
 * GroupedSummary
 *
 * A grouped chart: responses grouped by their answer to a label question, and per group either number questions
 * worked out (summed, averaged, counted, smallest, largest; the "numbers" mode, e.g. computers and printers per
 * school) or a second choice question's answers counted (the "split" mode, e.g. internet access per school type).
 *
 * Groups are the label's choices (each picked choice of a several-pick question counts the response), its yes / no or
 * its rating steps; or, for a short-text label such as a school's name, the answers themselves, trimmed and matched
 * whatever their case (shown as first written). Every group shows, in the chart's order. Numbers that aren't numbers
 * are counted as unreadable and left out.
 */
class GroupedSummary extends Summary
{
    /** @var array<string, array{label: string, count: int, stats: list<array{count: int, sum: float, min: ?float, max: ?float}>, split: array<string, int>}> */
    private array $groups = [];

    /** Number answers that weren't numbers. */
    private int $unreadableValues = 0;

    /**
     * @param  array{label: string, mode: string, series: list<array{question: string, aggregate: string}>, split: ?string, visual: string, sort: string, limit: int}  $rules
     * @param  list<Question>  $series  the series' questions, in the rules' order
     */
    public function __construct(Question $label, private readonly array $rules, private readonly array $series, private readonly ?Question $split)
    {
        parent::__construct($label);
    }

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        $labels = $this->labelsOf($value);
        if (! $labels) {
            return false;
        }

        foreach ($labels as $key => $label) {
            $this->groups[$key] ??= ['label' => $label, 'count' => 0, 'stats' => array_fill(0, count($this->series), ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null]), 'split' => []];
            $group = &$this->groups[$key];
            $group['count']++;

            $unreadable = [];
            foreach ($this->series as $index => $question) {
                $number = $data[$question->key()] ?? null;
                if ($number === null || $number === '') {
                    continue;
                }
                if (is_bool($number) || ! is_numeric($number)) {
                    // once per answer, though the question may feed several series
                    $unreadable[$question->key()] = true;

                    continue;
                }
                $number += 0;
                $stats = &$group['stats'][$index];
                $stats['count']++;
                $stats['sum'] += $number;
                $stats['min'] = $stats['min'] === null ? $number : min($stats['min'], $number);
                $stats['max'] = $stats['max'] === null ? $number : max($stats['max'], $number);
                unset($stats);
            }
            $this->unreadableValues += count($unreadable);

            if ($this->split) {
                foreach ($this->splitValues($data[$this->split->key()] ?? null) as $answer) {
                    $group['split'][$answer] = ($group['split'][$answer] ?? 0) + 1;
                }
            }
            unset($group);
        }

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        $series = $this->seriesHeads();
        $groups = $this->ordered();

        $rows = array_map(fn (array $group) => [
            'value' => $group['key'] ?? '',
            'label' => $group['label'],
            'responses' => $group['count'],
            'special' => $group['special'] ?? null,
            'values' => $this->values($group, $series),
        ], $groups);

        $visuals = ChartRules::groupedVisuals($this->rules['mode'], count($this->series));
        $groupCount = count($rows);

        return [
            'mode' => $this->rules['mode'],
            'series' => $series,
            'groups' => $rows,
            'unreadableValues' => $this->unreadableValues,
            'groupedVisuals' => array_values(array_unique([$this->rules['visual'], ...$visuals])),
            'heights' => [
                'bar' => max(140, $groupCount * (count($series) > 1 ? 12 * count($series) + 14 : 30) + 60),
                'stacked' => max(140, 32 * $groupCount + 90),
                'stacked100' => max(140, 32 * $groupCount + 90),
                'line' => 340,
                'donut' => max(260, 24 * $groupCount + 48),
            ],
        ];
    }

    /** {@inheritDoc} */
    public function toArray(): array
    {
        // the base names the label question's kind and visuals; a grouped chart has its own
        $summary = parent::toArray();
        $summary['kind'] = 'grouped';
        $summary['visuals'] = $summary['groupedVisuals'];
        unset($summary['groupedVisuals']);

        return $summary;
    }

    /**
     * The series the chart draws: each number question and how it's worked out, or the split question's answers.
     *
     * @return list<array{key: string, label: string}>
     */
    private function seriesHeads(): array
    {
        if ($this->split) {
            $answers = $this->split->kind === 'boolean'
                ? ['true' => $this->split->label('labelTrue') ?? __('Yes'), 'false' => $this->split->label('labelFalse') ?? __('No')]
                : $this->split->choices();
            $seen = [];
            foreach ($this->groups as $group) {
                $seen += $group['split'];
            }
            // answers the form doesn't list (a changed definition, choices from a web service) after its own
            foreach (array_keys($seen) as $answer) {
                $answers[$answer] ??= (string) $answer;
            }

            return array_map(fn ($key, $label) => ['key' => (string) $key, 'label' => $label], array_keys($answers), $answers);
        }

        $names = ['sum' => __('total'), 'avg' => __('average'), 'count' => __('count'), 'min' => __('smallest'), 'max' => __('largest')];

        return array_map(fn (Question $question, int $index) => [
            'key' => $question->name().':'.$this->rules['series'][$index]['aggregate'],
            'label' => $question->title().' ('.$names[$this->rules['series'][$index]['aggregate']].')',
        ], $this->series, array_keys($this->series));
    }

    /**
     * The groups in the chart's order, each with its key.
     *
     * @return list<array<string, mixed>>
     */
    private function ordered(): array
    {
        $groups = array_map(fn ($key, $group) => ['key' => (string) $key, ...$group], array_keys($this->groups), $this->groups);
        $measure = fn (array $group) => $this->split ? $group['count'] : ($this->aggregate($group['stats'][0] ?? null, $this->rules['series'][0]['aggregate']) ?? 0);

        return match ($this->rules['sort']) {
            'desc' => collect($groups)->sortByDesc($measure)->values()->all(),
            'asc' => collect($groups)->sortBy($measure)->values()->all(),
            'label' => collect($groups)->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
            default => $this->inFormOrder($groups),
        };
    }

    /**
     * The groups in the label question's own order (its choices or steps), answers it doesn't list after, as first seen.
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function inFormOrder(array $groups): array
    {
        $order = array_flip(array_map('strval', array_keys($this->question->choices())));

        return collect($groups)->sortBy(fn (array $group, int $seen) => [$order[$group['key']] ?? PHP_INT_MAX, $seen])->values()->all();
    }

    /**
     * A group's value for each series: a number worked out, or how many gave each split answer.
     *
     * @param  array<string, mixed>  $group
     * @param  list<array{key: string, label: string}>  $series
     * @return list<int|float|null>
     */
    private function values(array $group, array $series): array
    {
        if ($this->split) {
            return array_map(fn (array $head) => $group['split'][$head['key']] ?? 0, $series);
        }

        return array_map(fn (array $stats, int $index) => $this->aggregate($stats, $this->rules['series'][$index]['aggregate']), $group['stats'], array_keys($group['stats']));
    }

    /**
     * A series' numbers in a group, worked out (null when there are none to work out).
     *
     * @param  array{count: int, sum: float, min: ?float, max: ?float}|null  $stats
     */
    private function aggregate(?array $stats, string $aggregate): int|float|null
    {
        if (! $stats) {
            return null;
        }

        return match ($aggregate) {
            'count' => $stats['count'],
            // a whole total stays a whole number
            'sum' => floor($stats['sum']) == $stats['sum'] ? (int) $stats['sum'] : round($stats['sum'], 2),
            'avg' => $stats['count'] ? round($stats['sum'] / $stats['count'], 2) : null,
            'min' => $stats['min'],
            'max' => $stats['max'],
        };
    }

    /**
     * The groups an answer to the label question puts the response in, as key => label: each picked choice, yes / no,
     * a rating step, or short text (matched whatever its case).
     *
     * @return array<string, string>
     */
    private function labelsOf(mixed $value): array
    {
        $question = $this->question;

        if ($question->kind === 'boolean') {
            $element = $question->element;
            $answer = match (true) {
                array_key_exists('valueTrue', $element) && $value === $element['valueTrue'] => true,
                array_key_exists('valueFalse', $element) && $value === $element['valueFalse'] => false,
                $value === true || $value === 'true' => true,
                $value === false || $value === 'false' => false,
                default => null,
            };

            return $answer === null ? [] : [($answer ? 'true' : 'false') => $answer ? ($question->label('labelTrue') ?? __('Yes')) : ($question->label('labelFalse') ?? __('No'))];
        }

        if ($question->kind === 'text') {
            $text = is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value)) : '';

            return $text === '' ? [] : [mb_strtolower($text) => $this->groups[mb_strtolower($text)]['label'] ?? $text];
        }

        $choices = $question->choices();
        $labels = [];
        foreach (is_array($value) ? $value : [$value] as $item) {
            if (! is_scalar($item) || is_bool($item) || trim((string) $item) === '') {
                continue;
            }
            $key = (string) $item;
            $labels[$key] = $choices[$key] ?? match ($key) {
                'other' => $question->label('otherText') ?? __('Other'),
                'none' => $question->label('noneText') ?? __('None'),
                default => $key,
            };
        }

        return $labels;
    }

    /**
     * A split question's answer as the split answers it gives (several for a several-pick question).
     *
     * @return list<string>
     */
    private function splitValues(mixed $value): array
    {
        if ($value === true || $value === false) {
            return [$value ? 'true' : 'false'];
        }
        if ($this->split?->kind === 'boolean') {
            $element = $this->split->element;

            return match (true) {
                array_key_exists('valueTrue', $element) && $value === $element['valueTrue'] => ['true'],
                array_key_exists('valueFalse', $element) && $value === $element['valueFalse'] => ['false'],
                default => [],
            };
        }

        return array_values(array_map('strval', array_filter(is_array($value) ? $value : [$value], fn ($item) => is_scalar($item) && ! is_bool($item) && $item !== '')));
    }
}
