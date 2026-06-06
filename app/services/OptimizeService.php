<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionPayload;

class OptimizeService
{
    private string $attachDir;
    private string $publicBase;

    /**
     * Errors collected during the last optimize run.
     * Each entry: [ 'collection_id' => int, 'file' => string, 'reason' => string ]
     *
     * @var array<int, array{ collection_id: int, file: string, reason: string }>
     */
    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function __construct()
    {
        $appUrl = rtrim(_env('APP_URL'), '/');
        $storagePath = __DIR__ . '/../../storage';

        $this->attachDir  = $storagePath . '/app/public/attachments';
        $this->publicBase = $appUrl . '/storage/attachments';

        echo "Attachments directory: {$this->attachDir}\n";
    }

    // ─────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────

    /**
     * Optimize all unoptimized collections.
     *
     * @return array{ processed: int, files: int, errors: int }
     */
    public function optimizeAll(bool $dryRun = false): array
    {
        $this->errors = [];
        $collections = Collection::where('is_optimized', false)->get();
        return $this->processCollections($collections, $dryRun);
    }

    /**
     * Optimize a single collection by ID.
     *
     * @return array{ processed: int, files: int, errors: int }
     */
    public function optimizeOne(int $collectionId, bool $dryRun = false): array
    {
        $this->errors = [];
        $collections = Collection::where('id', $collectionId)->get();
        return $this->processCollections($collections, $dryRun);
    }

    /**
     * Re-optimize all collections, including already-optimized ones.
     *
     * @return array{ processed: int, files: int, errors: int }
     */
    public function forceOptimizeAll(bool $dryRun = false): array
    {
        $this->errors = [];
        $collections = Collection::all();
        return $this->processCollections($collections, $dryRun);
    }

    // ─────────────────────────────────────────────────────────────────
    // Core logic
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<Collection> $collections
     * @return array{ processed: int, files: int, errors: int }
     */
    private function processCollections(
        \Illuminate\Database\Eloquent\Collection $collections,
        bool $dryRun
    ): array {
        $stats = ['processed' => 0, 'files' => 0, 'errors' => 0];

        foreach ($collections as $collection) {
            $result = $this->processCollection($collection, $dryRun);

            $stats['processed']++;
            $stats['files']  += $result['files'];
            $stats['errors'] += $result['errors'];
        }

        return $stats;
    }

    /**
     * Process a single Collection: extract base64 files from its payload,
     * persist the files, update the submission JSON, and mark as optimized.
     *
     * @return array{ files: int, errors: int }
     */
    private function processCollection(Collection $collection, bool $dryRun): array
    {
        $payload = CollectionPayload::find($collection->id);

        if (!$payload || empty($payload->submission)) {
            // Nothing to do — still mark optimized to avoid re-visiting
            if (!$dryRun) {
                $collection->is_optimized = true;
                $collection->save();
            }
            return ['files' => 0, 'errors' => 0];
        }

        $changed   = false;
        $extracted = 0;
        $errors    = 0;

        $submission = $this->walkNode(
            $payload->submission,
            $collection->id,
            $dryRun,
            $changed,
            $extracted,
            $errors
        );

        if ($changed && !$dryRun) {
            // Verify every extracted file actually exists on disk before
            // committing the updated submission. If any file is missing,
            // record an error and abort the update for this collection.
            $missingFiles = $this->verifyFiles($submission);

            if (!empty($missingFiles)) {
                foreach ($missingFiles as $missing) {
                    $this->errors[] = [
                        'collection_id' => $collection->id,
                        'file'          => $missing['name'],
                        'reason'        => "File not found on disk after save: {$missing['path']}",
                    ];
                }
                // Do not persist — leave submission unchanged and unoptimized
                return ['files' => 0, 'errors' => count($missingFiles)];
            }

            $payload->submission = $submission;   // Eloquent re-encodes via cast
            $payload->save();
        }

        if (!$dryRun) {
            $collection->is_optimized = true;
            $collection->save();
        }

        return ['files' => $extracted, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────
    // Submission tree walker
    // ─────────────────────────────────────────────────────────────────

    /**
     * Recursively walk the decoded submission and replace every base64
     * file entry with its saved public URL.
     *
     * SurveyJS file questions produce values shaped like:
     *   [
     *     { "name": "photo.jpg", "type": "image/jpeg", "content": "data:image/jpeg;base64,..." },
     *     ...
     *   ]
     *
     * This shape is detected at any nesting depth (panels, dynamic panels,
     * matrices, etc.) and 'content' is replaced in-place.
     */
    private function walkNode(
        mixed $node,
        int   $collectionId,
        bool  $dryRun,
        bool  &$changed,
        int   &$extracted,
        int   &$errors
    ): mixed {
        if (!is_array($node)) {
            return $node;
        }

        // SurveyJS file entry: { name, content, type? }
        if (isset($node['name'], $node['content']) && $this->isBase64DataUri($node['content'])) {
            $url = $this->saveAttachment($node['content'], $node['name'], $collectionId, $dryRun, $errorReason);

            if ($url !== null) {
                $node['content'] = $url;
                $changed = true;
                $extracted++;
            } else {
                $errors++;
                $this->errors[] = [
                    'collection_id' => $collectionId,
                    'file'          => $node['name'],
                    'reason'        => $errorReason ?? 'Unknown error',
                ];
            }

            return $node;
        }

        // Recurse into all children
        foreach ($node as $key => $value) {
            $node[$key] = $this->walkNode($value, $collectionId, $dryRun, $changed, $extracted, $errors);
        }

        return $node;
    }

    // ─────────────────────────────────────────────────────────────────
    // File persistence
    // ─────────────────────────────────────────────────────────────────

    /**
     * Decode a base64 data URI and persist it to disk.
     *
     * Path convention:
     *   storage/app/public/attachments/YYYY/MM/DD/<sha1(binary)>.<ext>
     *
     * Using the sha1 of the file binary as the filename makes storage
     * content-addressed: identical files share one copy, and re-running
     * the optimizer is fully idempotent.
     *
     * Returns the public URL on success, null on failure.
     */
    private function saveAttachment(
        string  $dataUri,
        string  $originalName,
        int     $collectionId,
        bool    $dryRun,
        ?string &$errorReason = null
    ): ?string {
        if (!preg_match('/^data:([^;]+);base64,(.+)$/s', $dataUri, $m)) {
            $errorReason = 'Unparseable data URI';
            return null;
        }

        $mimeType = $m[1];
        $binary   = base64_decode($m[2], strict: true);

        if ($binary === false) {
            $errorReason = 'base64_decode failed';
            return null;
        }

        $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: $this->mimeToExt($mimeType);
        $sha1     = sha1($binary);
        $datePath = date('Y/m/d');
        $filename = $sha1 . ($ext ? '.' . $ext : '');

        $relPath   = "{$datePath}/{$filename}";
        $fullPath  = "{$this->attachDir}/{$relPath}";
        $publicUrl = "{$this->publicBase}/{$relPath}";

        if ($dryRun) {
            return $publicUrl;
        }

        $dir = dirname($fullPath);

        if (!is_dir($dir) && !mkdir($dir, 0755, recursive: true)) {
            $errorReason = "Could not create directory: {$dir}";
            return null;
        }

        if (!file_exists($fullPath) && file_put_contents($fullPath, $binary) === false) {
            $errorReason = "Could not write file: {$fullPath}";
            return null;
        }

        return $publicUrl;
    }

    // ─────────────────────────────────────────────────────────────────
    // File verification
    // ─────────────────────────────────────────────────────────────────

    /**
     * Walk the (already-processed) submission and confirm that every value
     * that looks like one of our public URLs has a corresponding file on disk.
     *
     * Returns an array of missing entries:
     *   [ 'name' => string, 'path' => string, 'url' => string ]
     */
    private function verifyFiles(mixed $node, array &$missing = []): array
    {
        if (!is_array($node)) {
            return $missing;
        }

        if (isset($node['name'], $node['content']) && $this->isOurUrl($node['content'])) {
            $diskPath = $this->urlToDiskPath($node['content']);

            if (!file_exists($diskPath)) {
                $missing[] = [
                    'name' => $node['name'],
                    'path' => $diskPath,
                    'url'  => $node['content'],
                ];
            }

            return $missing;
        }

        foreach ($node as $value) {
            $this->verifyFiles($value, $missing);
        }

        return $missing;
    }

    /**
     * Returns true if the value is a URL we wrote (starts with our public base).
     */
    private function isOurUrl(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, $this->publicBase . '/');
    }

    /**
     * Convert a public URL back to its absolute disk path.
     *
     * e.g. https://app.com/storage/attachments/2026/06/06/abc.jpg
     *   →  /var/www/storage/app/public/attachments/2026/06/06/abc.jpg
     */
    private function urlToDiskPath(string $url): string
    {
        $relative = substr($url, strlen($this->publicBase) + 1); // strip base + leading slash
        return $this->attachDir . '/' . $relative;
    }

    // ─────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────

    private function isBase64DataUri(mixed $value): bool
    {
        return is_string($value)
            && str_starts_with($value, 'data:')
            && str_contains($value, ';base64,');
    }

    private function mimeToExt(string $mime): string
    {
        return match (strtolower($mime)) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'image/svg+xml'   => 'svg',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
            'text/plain'      => 'txt',
            'text/csv'        => 'csv',
            'audio/mpeg'      => 'mp3',
            'audio/wav'       => 'wav',
            'video/mp4'       => 'mp4',
            default           => '',
        };
    }
}