<?php

namespace App\Support\Analytics\Summaries;

/**
 * RatingSummary
 *
 * A rating: how many gave each step (every step listed, unpicked ones at 0), and the mean, median, min and max when
 * the steps are numbers. A 0–10 rating is read as Net Promoter Score too: promoters (9–10) less detractors (0–6), as
 * a share of the answers, from -100 to 100.
 */
class RatingSummary extends Summary
{
    /** @var array<string, int> answers per step */
    private array $counts = [];

    /** @var list<int|float> numeric answers */
    private array $numbers = [];

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        if (! is_scalar($value) || is_bool($value)) {
            return false;
        }

        $step = (string) $value;
        $this->counts[$step] = ($this->counts[$step] ?? 0) + 1;
        if (is_numeric($value)) {
            $this->numbers[] = $value + 0;
        }

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        $counts = $this->counts;
        $rows = [];
        foreach ($this->question->choices() as $value => $label) {
            $rows[] = ['value' => (string) $value, 'label' => $label, 'count' => $counts[$value] ?? 0, 'percent' => self::percent($counts[$value] ?? 0, $this->answered), 'special' => null];
            unset($counts[$value]);
        }
        // answers off the scale (a definition changed since) still count, after the steps
        ksort($counts, SORT_NATURAL);
        foreach ($counts as $value => $count) {
            $rows[] = ['value' => (string) $value, 'label' => (string) $value, 'count' => $count, 'percent' => self::percent($count, $this->answered), 'special' => null];
        }

        $details = ['categories' => $rows, 'stats' => self::stats($this->numbers), 'max' => $this->scaleMax()];

        if ($this->question->kind === 'nps') {
            $detractors = count(array_filter($this->numbers, fn ($number) => $number <= 6));
            $passives = count(array_filter($this->numbers, fn ($number) => $number > 6 && $number <= 8));
            $promoters = count(array_filter($this->numbers, fn ($number) => $number > 8));
            $scored = $detractors + $passives + $promoters;
            $details['nps'] = [
                'score' => $scored ? (int) round(($promoters - $detractors) / $scored * 100) : null,
                'detractors' => ['count' => $detractors, 'percent' => self::percent($detractors, $scored)],
                'passives' => ['count' => $passives, 'percent' => self::percent($passives, $scored)],
                'promoters' => ['count' => $promoters, 'percent' => self::percent($promoters, $scored)],
            ];
        }

        return $details;
    }

    /** The scale's top step, when it's a number. */
    private function scaleMax(): int|float|null
    {
        $steps = array_keys($this->question->choices());
        $last = end($steps);

        return is_numeric($last) ? $last + 0 : null;
    }
}
