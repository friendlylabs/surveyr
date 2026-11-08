<?php

namespace App\Services;

use App\Models\Collection;

/**
 * Packs the files a form's submissions carry into one ZIP.
 *
 *   RSP-12/<question>/<stored file>      file uploaded through media.store (hashed name kept)
 *   RSP-12/<question>/<question>.png     inline data: URI (signature pads, offline clients)
 *   manifest.csv                         one line per file, including the ones left out
 *
 * Only files under storage/app/public are read; anything pointing elsewhere is
 * listed in the manifest as external and never fetched.
 */
class AttachmentExport
{
    /** Question types whose answers are files. */
    public const TYPES = ['file', 'signaturepad'];

    public const MAX_FILES = 2000;
    public const MAX_BYTES = 500 * 1024 * 1024;

    private const MIME_EXT = [
        'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'image/svg+xml' => 'svg', 'application/pdf' => 'pdf', 'audio/webm' => 'webm', 'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav', 'video/mp4' => 'mp4', 'text/plain' => 'txt', 'text/csv' => 'csv',
    ];

    /**
     * File questions in a survey, as data paths. A plain question is ['name'];
     * one inside a dynamic panel is ['panel', '*', 'name'], since its answers live
     * in an array under the panel's value.
     *
     * @return array<int, array{label: string, path: string[]}>
     */
    public static function questions(mixed $survey): array
    {
        $found = [];
        $walk = function ($elements, array $prefix) use (&$walk, &$found) {
            if (!is_array($elements)) return;
            foreach ($elements as $el) {
                if (!is_array($el) || !isset($el['name'])) continue;
                $type = $el['type'] ?? 'panel';
                $key  = (string) ($el['valueName'] ?? $el['name']);

                if (in_array($type, self::TYPES, true)) {
                    $path = array_merge($prefix, [$key]);
                    $found[] = ['label' => implode('_', array_diff($path, ['*'])), 'path' => $path];
                } elseif ($type === 'paneldynamic') {
                    $walk($el['templateElements'] ?? null, array_merge($prefix, [$key, '*']));
                } else {
                    // static panels don't nest data, their questions stay at this level
                    $walk($el['elements'] ?? null, $prefix);
                }
            }
        };

        if (is_array($survey)) {
            foreach ($survey['pages'] ?? [] as $page) $walk($page['elements'] ?? null, []);
            $walk($survey['elements'] ?? null, []);
        }

        return $found;
    }

    public function __construct(private int $formId, private array $questions) {}

    /**
     * Builds the ZIP into a temp file.
     *
     * @param ?array $filters  the submissions page's filters (ignored when ids are given)
     * @param int[]  $ids      selected submission ids; empty means every row the filters match
     * @return array{path: ?string, files: int, error: ?string}
     */
    public function build(?array $filters, array $ids = []): array
    {
        $entries = [];
        $bytes = 0;

        $query = $ids
            ? Collection::where('form_id', $this->formId)->whereIn('id', $ids)
            : Collection::filteredQuery($this->formId, $filters);

        $overLimit = false;
        $query->with('payload')->chunkById(200, function ($rows) use (&$entries, &$bytes, &$overLimit) {
            foreach ($rows as $row) {
                $submission = $row->payload?->submission;
                if (is_string($submission)) $submission = json_decode($submission, true);
                if (!is_array($submission)) continue;

                foreach ($this->entriesFor($row, $submission) as $entry) {
                    $entries[] = $entry;
                    $bytes += $entry['size'];
                }

                if (count($entries) > self::MAX_FILES || $bytes > self::MAX_BYTES) {
                    $overLimit = true;
                    return false;
                }
            }
        });

        if ($overLimit) {
            return ['path' => null, 'files' => 0, 'error' => sprintf(
                'Too many attachments to export at once (limit %d files / %d MB). Narrow the selection or apply a filter.',
                self::MAX_FILES, self::MAX_BYTES / 1024 / 1024
            )];
        }

        $included = array_filter($entries, fn ($e) => $e['status'] === 'included');
        if (!$included) {
            return ['path' => null, 'files' => 0, 'error' => $entries
                ? 'None of the attachments in this selection could be found on the server.'
                : 'There are no attachments in this selection.'];
        }

        $path = tempnam(sys_get_temp_dir(), 'att');
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            return ['path' => null, 'files' => 0, 'error' => 'Could not create the archive.'];
        }

        $used = [];
        $manifest = fopen('php://temp', 'r+');
        fputcsv($manifest, ['response', 'submitted', 'question', 'file', 'status']);

        foreach ($entries as $entry) {
            $name = $entry['name'];
            if ($entry['status'] === 'included') {
                // the same file can be referenced twice in one answer; keep one copy
                if (!isset($used[$name])) {
                    $used[$name] = true;
                    $entry['source'] === 'disk'
                        ? $zip->addFile($entry['data'], $name)
                        : $zip->addFromString($name, base64_decode($entry['data']));
                    // attachments are mostly already-compressed media
                    $zip->setCompressionName($name, \ZipArchive::CM_STORE);
                }
            }
            fputcsv($manifest, [$entry['response'], $entry['submitted'], $entry['question'], $entry['status'] === 'included' ? $name : $entry['reference'], $entry['status']]);
        }

        rewind($manifest);
        $zip->addFromString('manifest.csv', stream_get_contents($manifest));
        fclose($manifest);

        if (!$zip->close()) {
            @unlink($path);
            return ['path' => null, 'files' => 0, 'error' => 'Could not write the archive.'];
        }

        return ['path' => $path, 'files' => count($used), 'error' => null];
    }

    /** Every file reference in one submission, resolved to something the ZIP can read. */
    private function entriesFor($row, array $submission): array
    {
        $response = 'RSP-' . $row->id;
        $entries = [];

        foreach ($this->questions as $question) {
            foreach ($this->valuesAt($submission, $question['path']) as $i => [$value, $instance]) {
                $folder = $response . '/' . $this->safe($question['label'] . ($instance !== null ? '_' . ($instance + 1) : ''));

                foreach ($this->references($value) as $n => $reference) {
                    $entry = $this->resolve($reference, $folder, $question['label'], $n);
                    $entry['response']  = $response;
                    $entry['submitted'] = $row->created_at?->toDateTimeString();
                    $entry['question']  = $question['label'] . ($instance !== null ? ' #' . ($instance + 1) : '');
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Values at a data path. '*' fans out over a dynamic panel's entries.
     * Returns [value, panel index or null] pairs.
     */
    private function valuesAt(array $data, array $path, ?int $instance = null): array
    {
        $key = array_shift($path);
        if ($key === '*') {
            $out = [];
            foreach (array_values($data) as $i => $item) {
                if (is_array($item)) array_push($out, ...$this->valuesAt($item, $path, $i));
            }
            return $out;
        }

        if (!array_key_exists($key, $data)) return [];
        if (!$path) return [[$data[$key], $instance]];
        return is_array($data[$key]) ? $this->valuesAt($data[$key], $path, $instance) : [];
    }

    /** File answers are [{name, type, content}], signature pads a single string. */
    private function references(mixed $value): array
    {
        if (is_string($value)) return $value !== '' ? [$value] : [];
        if (!is_array($value)) return [];

        $refs = [];
        foreach ($value as $item) {
            $content = is_array($item) ? ($item['content'] ?? null) : $item;
            if (is_string($content) && $content !== '') $refs[] = $content;
        }
        return $refs;
    }

    private function resolve(string $reference, string $folder, string $label, int $n): array
    {
        $base = ['reference' => strlen($reference) > 200 ? substr($reference, 0, 200) . '…' : $reference, 'size' => 0, 'name' => '', 'source' => null, 'data' => null];

        if (str_starts_with($reference, 'data:')) {
            if (!preg_match('#^data:([\w.+-]+/[\w.+-]+)?(?:;[^,]*)?;base64,(.*)$#s', $reference, $m)) {
                return $base + ['status' => 'invalid'];
            }
            $ext = self::MIME_EXT[strtolower($m[1] ?? '')] ?? 'bin';
            return array_merge($base, [
                'status' => 'included',
                'source' => 'inline',
                'data'   => $m[2],
                'size'   => (int) (strlen($m[2]) * 3 / 4),
                'name'   => $folder . '/' . $this->safe($label) . ($n ? '_' . ($n + 1) : '') . '.' . $ext,
            ]);
        }

        // our own uploads: match on the path, not the host, so links survive a domain change
        $urlPath = rawurldecode((string) parse_url($reference, PHP_URL_PATH));
        $at = strpos($urlPath, '/storage/');
        if ($at === false) return $base + ['status' => 'external'];

        $root = realpath(StoragePath('app/public'));
        $file = realpath(StoragePath('app/public/' . substr($urlPath, $at + strlen('/storage/'))));
        if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
            return $base + ['status' => 'missing'];
        }

        return array_merge($base, [
            'status' => 'included',
            'source' => 'disk',
            'data'   => $file,
            'size'   => (int) filesize($file),
            'name'   => $folder . '/' . $this->safe(basename($file)),
        ]);
    }

    private function safe(string $segment): string
    {
        $segment = preg_replace('/[^A-Za-z0-9._-]+/', '_', $segment);
        return trim($segment, '._') ?: 'file';
    }
}
