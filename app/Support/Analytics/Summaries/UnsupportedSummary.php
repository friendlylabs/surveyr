<?php

namespace App\Support\Analytics\Summaries;

/**
 * UnsupportedSummary
 *
 * A question the visualizer can't summarise yet (dynamic panels, dropdown matrices, multiple text, custom types):
 * only how many answered.
 */
class UnsupportedSummary extends Summary
{
    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        return [];
    }
}
