<?php

namespace App\Support;

use App\Exceptions\ExportFailed;
use App\Models\Submission;
use App\Models\Upload;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * AttachmentsExport
 *
 * Gathers the files a form's responses carry into a folder, as the legacy app's attachments export did:
 *   RSP-12/<question>/<file name>         a file question's upload (its own name; numbered when two share one)
 *   RSP-12/<question>/<question>.png      a signature pad's drawing
 *   RSP-12/<panel_question_2>/...         a question inside a dynamic panel, one folder per entry
 *   manifest.csv                          one line per file, those left out too, and why
 * under an optional prefix (attachments/ when they go beside a workbook). Files are taken from the storage they were
 * uploaded to (an answer's "upload:{hash}", the response's own uploads only) or from the answer itself (a data URL:
 * signatures, older responses); a link to anywhere else (a legacy upload, another site) is listed in the manifest as
 * external and never fetched, and an upload whose file is gone as missing. Stops at MAX_FILES files or MAX_BYTES bytes.
 */
class AttachmentsExport
{
    /** Question types whose answers are files. */
    const TYPES = ['file', 'signaturepad'];

    /** The most files, and bytes, one export holds. */
    const MAX_FILES = 2000;

    const MAX_BYTES = 500 * 1024 * 1024;

    /** File extensions by type, for files that come without a name (signatures). */
    const EXTENSIONS = [
        'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf', 'audio/webm' => 'webm', 'audio/mpeg' => 'mp3', 'audio/wav' => 'wav', 'video/mp4' => 'mp4',
        'text/plain' => 'txt', 'text/csv' => 'csv',
    ];

    /** @var list<array{response: string, submitted: string, question: string, file: string, status: string}> */
    private array $manifest = [];

    private int $files = 0;

    private int $bytes = 0;

    /**
     * @param  list<array{label: string, path: list<string>}>  $questions  from questions()
     * @param  string  $folder  where the files are written
     * @param  string  $prefix  the files' folder inside it ('' or e.g. 'attachments/')
     */
    public function __construct(private array $questions, private string $folder, private string $prefix = '') {}

    /**
     * A survey's file questions as paths into the answers: ['photo'] for a plain one, ['household', '*', 'photo'] for
     * one inside a dynamic panel (whose answer is a list of entries). Static panels don't nest answers.
     *
     * @return list<array{label: string, path: list<string>}>
     */
    public static function questions(?array $content): array
    {
        $found = [];
        $walk = function ($elements, array $prefix) use (&$walk, &$found) {
            foreach (is_array($elements) ? $elements : [] as $element) {
                if (! is_array($element) || ! isset($element['name'])) {
                    continue;
                }
                $type = $element['type'] ?? 'panel';
                $key = (string) ($element['valueName'] ?? $element['name']);

                if (in_array($type, self::TYPES, true)) {
                    $path = [...$prefix, $key];
                    $found[] = ['label' => implode('_', array_diff($path, ['*'])), 'path' => $path];
                } elseif ($type === 'paneldynamic') {
                    $walk($element['templateElements'] ?? null, [...$prefix, $key, '*']);
                } else {
                    $walk($element['elements'] ?? null, $prefix);
                }
            }
        };
        foreach ($content['pages'] ?? [] as $page) {
            $walk($page['elements'] ?? null, []);
        }
        $walk($content['elements'] ?? null, []);

        return $found;
    }

    /**
     * Writes one response's files and returns, by question label, the paths they were written to.
     *
     * @return array<string, list<string>>
     *
     * @throws ExportFailed past MAX_FILES files or MAX_BYTES bytes
     */
    public function add(Submission $response): array
    {
        $written = [];
        $uploads = $response->uploads->keyBy('hash');
        foreach ($this->questions as $question) {
            foreach ($this->valuesAt((array) $response->data, $question['path']) as [$value, $entry]) {
                $label = $question['label'].($entry !== null ? '_'.($entry + 1) : '');
                $folder = $response->reference.'/'.self::safeName($label);

                foreach ($this->files($value, $question['label'], $uploads) as $file) {
                    $line = ['response' => $response->reference, 'submitted' => $response->created_at->toDateTimeString(), 'question' => $label];
                    if ($file['status'] !== 'included') {
                        $this->manifest[] = $line + ['file' => $file['reference'], 'status' => $file['status']];

                        continue;
                    }

                    $path = $this->unique($this->prefix.$folder.'/'.self::safeName($file['name']), $written[$label] ?? []);
                    if (! $this->write($path, $file['bytes'] ?? $file['upload'])) {
                        $this->manifest[] = $line + ['file' => $file['reference'], 'status' => 'missing'];

                        continue;
                    }
                    $written[$label][] = $path;
                    $this->manifest[] = $line + ['file' => $path, 'status' => 'included'];
                }
            }
        }

        return $written;
    }

    /** How many files were written. */
    public function count(): int
    {
        return $this->files;
    }

    /** Writes manifest.csv (response, submitted, question, file, status) beside the files. */
    public function writeManifest(): void
    {
        $handle = fopen($this->folder.'/'.$this->prefix.'manifest.csv', 'w');
        fputcsv($handle, ['response', 'submitted', 'question', 'file', 'status']);
        foreach ($this->manifest as $line) {
            fputcsv($handle, array_values($line));
        }
        fclose($handle);
    }

    /** A name that's safe as a file or folder name on any system (no path separators or reserved characters). */
    public static function safeName(string $name): string
    {
        $name = trim(preg_replace('~[\\\\/:*?"<>|\x00-\x1F]+~u', '-', $name), ' .-');

        return Str::limit($name, 100, '') ?: 'file';
    }

    /**
     * The answers at a path; '*' runs over a dynamic panel's entries. Returns [answer, entry index or null] pairs.
     *
     * @return list<array{0: mixed, 1: int|null}>
     */
    private function valuesAt(array $data, array $path, ?int $entry = null): array
    {
        $key = array_shift($path);
        if ($key === '*') {
            $values = [];
            foreach (array_values($data) as $index => $item) {
                if (is_array($item)) {
                    array_push($values, ...$this->valuesAt($item, $path, $index));
                }
            }

            return $values;
        }
        if (! array_key_exists($key, $data)) {
            return [];
        }
        if (! $path) {
            return [[$data[$key], $entry]];
        }

        return is_array($data[$key]) ? $this->valuesAt($data[$key], $path, $entry) : [];
    }

    /**
     * The files in an answer: a file question's [{name, type, content}], a signature pad's data URL. Each with its
     * upload or bytes when it can be taken (status included), or why it can't.
     *
     * @param  Collection<string, Upload>  $uploads  the response's uploads, by hash
     * @return list<array{name: string, bytes?: string, upload?: Upload, status: string, reference: string}>
     */
    private function files(mixed $value, string $label, Collection $uploads): array
    {
        $items = is_string($value) ? [['name' => null, 'content' => $value]] : (is_array($value) && array_is_list($value) ? $value : []);
        $files = [];
        foreach ($items as $index => $item) {
            $content = is_array($item) ? ($item['content'] ?? null) : null;
            if (! is_string($content) || $content === '') {
                continue;
            }
            $given = is_string($item['name'] ?? null) && $item['name'] !== '' ? $item['name'] : null;

            $hash = Upload::hashFrom($content);
            if ($hash !== null) {
                $upload = $uploads->get($hash);
                $files[] = $upload
                    ? ['name' => $given ?? $upload->name, 'upload' => $upload, 'status' => 'included', 'reference' => $content]
                    : ['name' => $given ?? 'file', 'status' => 'missing', 'reference' => $content];

                continue;
            }

            $file = self::decode($content);
            $files[] = array_filter([
                'name' => $given ?? $label.($index ? '_'.($index + 1) : '').'.'.(self::EXTENSIONS[$file['type'] ?? ''] ?? 'bin'),
                'bytes' => $file['bytes'] ?? null,
                'status' => $file ? 'included' : (str_starts_with($content, 'data:') ? 'invalid' : 'external'),
                'reference' => Str::limit($content, 200),
            ], fn ($part) => $part !== null);
        }

        return $files;
    }

    /**
     * A data URL's type and bytes, or null when it isn't one.
     *
     * @return array{type: string, bytes: string}|null
     */
    public static function decode(string $content): ?array
    {
        if (! preg_match('/^data:([\w.+-]+\/[\w.+-]+)?(;[^,]*)?,(.*)$/s', $content, $parts)) {
            return null;
        }
        $bytes = str_contains($parts[2], 'base64') ? base64_decode($parts[3], true) : rawurldecode($parts[3]);

        return $bytes === false ? null : ['type' => strtolower($parts[1] ?: 'application/octet-stream'), 'bytes' => $bytes];
    }

    /** The path, numbered (name (2).ext) when it's already taken in the same folder. */
    private function unique(string $path, array $taken): string
    {
        $info = pathinfo($path);
        $extension = isset($info['extension']) ? '.'.$info['extension'] : '';
        for ($copy = 2, $candidate = $path; in_array($candidate, $taken, true); $copy++) {
            $candidate = $info['dirname'].'/'.$info['filename']." ({$copy})".$extension;
        }

        return $candidate;
    }

    /**
     * Writes a file (its bytes, or copied from the storage it was uploaded to), keeping count; past the limits, stops
     * the export. False when an upload's file can't be read from its storage.
     */
    private function write(string $path, string|Upload $file): bool
    {
        $this->files++;
        $this->bytes += is_string($file) ? strlen($file) : $file->size;
        if ($this->files > self::MAX_FILES || $this->bytes > self::MAX_BYTES) {
            throw new ExportFailed(__('Too many attachments to export at once (at most :files files or :mb MB). Narrow the selection or filter the responses.', [
                'files' => number_format(self::MAX_FILES), 'mb' => self::MAX_BYTES / 1024 / 1024,
            ]));
        }

        $target = $this->folder.'/'.$path;
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }
        if (is_string($file)) {
            return file_put_contents($target, $file) !== false;
        }

        try {
            $stream = $file->storage()->readStream($file->path);
        } catch (Throwable) {
            $stream = null;
        }
        if (! is_resource($stream)) {
            $this->files--;
            $this->bytes -= $file->size;

            return false;
        }
        $out = fopen($target, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        return true;
    }
}
