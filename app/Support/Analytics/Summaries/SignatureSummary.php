<?php

namespace App\Support\Analytics\Summaries;

/**
 * SignatureSummary
 *
 * A signature pad: how many signed. The drawings are never sent to the page.
 */
class SignatureSummary extends Summary
{
    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        return is_string($value) && (str_starts_with($value, 'data:') || str_starts_with($value, 'upload:'));
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        return [];
    }
}
