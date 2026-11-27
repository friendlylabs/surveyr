<?php

namespace App\Support\Analytics\Summaries;

/**
 * MatrixSummary
 *
 * A single-choice matrix ({row: column}): how many picked each column in each row, as a share of the row's answers.
 * Rows and columns the form no longer lists still count, after its own.
 */
class MatrixSummary extends Summary
{
    /** @var array<string, array<string, int>> picks per row and column */
    private array $counts = [];

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        if (! is_array($value) || array_is_list($value)) {
            return false;
        }

        $read = false;
        foreach ($value as $row => $column) {
            if (is_scalar($column) && ! is_bool($column)) {
                $this->counts[(string) $row][(string) $column] = ($this->counts[(string) $row][(string) $column] ?? 0) + 1;
                $read = true;
            }
        }

        return $read;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        $rows = $this->question->matrix('rows');
        $columns = $this->question->matrix('columns');
        foreach ($this->counts as $row => $picks) {
            $rows[$row] ??= (string) $row;
            foreach (array_keys($picks) as $column) {
                $columns[$column] ??= (string) $column;
            }
        }

        $table = [];
        foreach ($rows as $row => $label) {
            $total = array_sum($this->counts[$row] ?? []);
            $table[] = [
                'value' => (string) $row,
                'label' => $label,
                'total' => $total,
                'cells' => array_map(fn ($column) => [
                    'count' => $this->counts[$row][$column] ?? 0,
                    'percent' => self::percent($this->counts[$row][$column] ?? 0, $total),
                ], array_map('strval', array_keys($columns))),
            ];
        }

        return [
            'columns' => array_map(fn ($value, $label) => ['value' => (string) $value, 'label' => $label], array_keys($columns), $columns),
            'rows' => $table,
        ];
    }
}
