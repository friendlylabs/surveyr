<?php

namespace App\Support\Analytics\Summaries;

use App\Support\Analytics\Question;

/**
 * Summary
 *
 * Counts one question's answers as responses stream past (add()), then describes them as plain data (toArray()): how
 * many responses there were, answered it, skipped it, or held an answer in a shape it can't read, plus what the kind
 * adds (choices with counts, numbers, a matrix, ...). The visualizer's JS engine draws it.
 */
abstract class Summary
{
    /** Responses seen. */
    protected int $total = 0;

    /** Responses with a readable answer. */
    protected int $answered = 0;

    /** Responses with an answer it couldn't read. */
    protected int $unreadable = 0;

    public function __construct(public readonly Question $question) {}

    /**
     * Counts one response.
     *
     * @param  array<string, mixed>  $data  the response's answers
     * @param  array{hash: string, reference: string}  $response
     */
    public function add(array $data, array $response): void
    {
        $this->total++;
        $value = $data[$this->question->key()] ?? null;
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        if ($this->collect($value, $data, $response)) {
            $this->answered++;
        } else {
            $this->unreadable++;
        }
    }

    /**
     * The summary as plain data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->question->name(),
            'key' => $this->question->key(),
            'title' => $this->question->title(),
            'type' => $this->question->type(),
            'kind' => $this->question->kind,
            'visuals' => $this->question->visuals(),
            'total' => $this->total,
            'answered' => $this->answered,
            'skipped' => $this->total - $this->answered - $this->unreadable,
            'unreadable' => $this->unreadable,
            'percent' => self::percent($this->answered, $this->total),
        ] + $this->details();
    }

    /**
     * Counts one answer (never empty); false when its shape can't be read.
     *
     * @param  array<string, mixed>  $data
     * @param  array{hash: string, reference: string}  $response
     */
    abstract protected function collect(mixed $value, array $data, array $response): bool;

    /**
     * What the kind adds to the summary.
     *
     * @return array<string, mixed>
     */
    abstract protected function details(): array;

    /** A share as a percentage with one decimal (0 when there's nothing to share). */
    protected static function percent(int|float $count, int|float $base): float
    {
        return $base > 0 ? round($count / $base * 100, 1) : 0.0;
    }

    /**
     * Mean, median, min and max of numbers (nulls when there are none).
     *
     * @param  list<int|float>  $numbers
     * @return array{count: int, mean: ?float, median: ?float, min: int|float|null, max: int|float|null}
     */
    protected static function stats(array $numbers): array
    {
        $count = count($numbers);
        if ($count === 0) {
            return ['count' => 0, 'mean' => null, 'median' => null, 'min' => null, 'max' => null];
        }

        sort($numbers);
        $middle = intdiv($count, 2);
        $median = $count % 2 ? $numbers[$middle] : ($numbers[$middle - 1] + $numbers[$middle]) / 2;

        return [
            'count' => $count,
            'mean' => round(array_sum($numbers) / $count, 2),
            'median' => round($median, 2),
            'min' => $numbers[0],
            'max' => $numbers[$count - 1],
        ];
    }
}
