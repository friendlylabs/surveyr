<?php

namespace App\Support\Analytics\Summaries;

/**
 * NumberSummary
 *
 * A number answer (a text box taking numbers, an expression): count, mean, median, min and max, and how the answers
 * spread over at most BINS ranges (one per whole number when they span fewer). Answers that aren't numbers are
 * unreadable.
 */
class NumberSummary extends Summary
{
    /** Most ranges the spread is shown in. */
    const BINS = 10;

    /** @var list<int|float> */
    private array $numbers = [];

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        if (is_bool($value) || ! is_numeric($value)) {
            return false;
        }
        $this->numbers[] = $value + 0;

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        return ['stats' => self::stats($this->numbers), 'categories' => $this->bins()];
    }

    /**
     * The answers' spread as ranges with counts.
     *
     * @return list<array{value: string, label: string, count: int, percent: float, special: null}>
     */
    private function bins(): array
    {
        if (! $this->numbers) {
            return [];
        }

        $min = min($this->numbers);
        $max = max($this->numbers);
        $whole = collect($this->numbers)->every(fn ($number) => is_int($number) || floor($number) == $number);

        // whole numbers over a short span: one bar each
        if ($whole && $max - $min < self::BINS) {
            $counts = array_fill_keys(range((int) $min, (int) $max), 0);
            foreach ($this->numbers as $number) {
                $counts[(int) $number]++;
            }

            return array_map(fn ($value, $count) => $this->bin((string) $value, (string) $value, $count), array_keys($counts), $counts);
        }

        $width = ($max - $min) / self::BINS;
        $counts = array_fill(0, self::BINS, 0);
        foreach ($this->numbers as $number) {
            $counts[min(self::BINS - 1, (int) floor(($number - $min) / $width))]++;
        }

        return array_map(function ($index, $count) use ($min, $width) {
            $label = self::format($min + $index * $width).' – '.self::format($min + ($index + 1) * $width);

            return $this->bin((string) $index, $label, $count);
        }, array_keys($counts), $counts);
    }

    /**
     * One range of the spread.
     *
     * @return array{value: string, label: string, count: int, percent: float, special: null}
     */
    private function bin(string $value, string $label, int $count): array
    {
        return ['value' => $value, 'label' => $label, 'count' => $count, 'percent' => self::percent($count, $this->answered), 'special' => null];
    }

    /** A range end: at most two decimals, no trailing zeros. */
    private static function format(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
