<?php

namespace App\Support\Analytics\Summaries;

/**
 * FileSummary
 *
 * A file question: how many responses sent files, how many files in all, by type (extension), and the latest LATEST
 * files with the response they came in, for links (FormResponsesController@file). Older answers holding one file as a
 * data URL count as one file.
 */
class FileSummary extends Summary
{
    /** How many of the latest files are listed. */
    const LATEST = 10;

    /** Files in all. */
    private int $files = 0;

    /** @var array<string, int> files per extension */
    private array $types = [];

    /** @var list<array{name: string, response: string, reference: string, index: int}> */
    private array $latest = [];

    /** {@inheritDoc} */
    protected function collect(mixed $value, array $data, array $response): bool
    {
        $files = is_string($value) ? [['name' => '', 'type' => preg_match('#^data:([^;,]+)#', $value, $mime) ? $mime[1] : '']] : (is_array($value) && array_is_list($value) ? array_filter($value, 'is_array') : []);
        if (! $files) {
            return false;
        }

        foreach ($files as $index => $file) {
            $name = is_string($file['name'] ?? null) ? $file['name'] : '';
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION))
                ?: strtolower((string) preg_replace('#^[^/]*/#', '', is_string($file['type'] ?? null) ? $file['type'] : ''));
            $this->types[$extension ?: '—'] = ($this->types[$extension ?: '—'] ?? 0) + 1;
            $this->files++;
            $this->latest[] = ['name' => $name ?: __('File :number', ['number' => $index + 1]), 'response' => $response['hash'], 'reference' => $response['reference'], 'index' => $index];
        }
        // responses stream oldest first; keep the newest few
        $this->latest = array_slice($this->latest, -self::LATEST);

        return true;
    }

    /** {@inheritDoc} */
    protected function details(): array
    {
        arsort($this->types);

        return [
            'files' => $this->files,
            'categories' => array_map(fn ($type, $count) => [
                'value' => (string) $type, 'label' => strtoupper((string) $type), 'count' => $count, 'percent' => self::percent($count, $this->files), 'special' => null,
            ], array_keys($this->types), $this->types),
            'latest' => array_reverse($this->latest),
        ];
    }
}
