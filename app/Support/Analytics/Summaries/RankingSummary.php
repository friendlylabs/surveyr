<?php

namespace App\Support\Analytics\Summaries;

/**
 * RankingSummary
 *
 * A ranking: each choice's average position (1 is the top; lower is better), how often it was ranked first, and how
 * many answers left it unranked (select-to-rank lets people rank only some). Sorted best first.
 */
class RankingSummary extends Summary
{
    /** @var array<string, array{sum: int, ranked: int, first: int}> */
    private array $positions = [];

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return false;
        }

        foreach (array_values(array_unique(array_filter($value, 'is_scalar'))) as $index => $item) {
            $item = (string) $item;
            $this->positions[$item] ??= ['sum' => 0, 'ranked' => 0, 'first' => 0];
            $this->positions[$item]['sum'] += $index + 1;
            $this->positions[$item]['ranked']++;
            $this->positions[$item]['first'] += $index === 0 ? 1 : 0;
        }

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        $labels = $this->question->choices() + array_combine(array_keys($this->positions), array_keys($this->positions));

        $rows = [];
        foreach ($labels as $value => $label) {
            $position = $this->positions[$value] ?? ['sum' => 0, 'ranked' => 0, 'first' => 0];
            $rows[] = [
                'value' => (string) $value,
                'label' => $label,
                'average' => $position['ranked'] ? round($position['sum'] / $position['ranked'], 2) : null,
                'first' => $position['first'],
                'ranked' => $position['ranked'],
                'unranked' => $this->answered - $position['ranked'],
            ];
        }
        usort($rows, fn ($a, $b) => [$a['average'] === null, $a['average']] <=> [$b['average'] === null, $b['average']]);

        return ['ranking' => $rows, 'positions' => count($labels)];
    }
}
