<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use App\Support\FileStorage;
use Database\Factories\UploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * Upload
 *
 * A file a respondent uploaded to a form, kept privately in the storage that was chosen when it came in (disk: local,
 * s3 or r2, FileStorage), so switching storage later leaves it where it is. Answers refer to it as "upload:{hash}".
 * It belongs to no submission until its response is sent; uploads never sent are pruned after ORPHAN_HOURS hours
 * (model:prune, routes/console.php), their files with them. Never public: it's seen only
 * through a short-lived signed link (Public\FilesController).
 */
#[Fillable(['disk', 'path', 'name', 'type', 'size'])]
#[Hidden(['id', 'workspace_id', 'form_id', 'submission_id', 'disk', 'path'])]
class Upload extends Model
{
    /** @use HasFactory<UploadFactory> */
    use BelongsToWorkspace, HasFactory, HasHash, Prunable;

    /** How answers refer to an upload: "upload:{hash}". */
    const PREFIX = 'upload:';

    /** How long an upload waits for its response to be sent before it's pruned, in hours. */
    const ORPHAN_HOURS = 24;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** Deleting it deletes its file. */
    protected static function booted(): void
    {
        static::deleted(fn (Upload $upload) => $upload->storage()->delete($upload->path));
    }

    /** Uploads whose response was never sent, ORPHAN_HOURS hours on (pruning deletes each, and so its file). */
    public function prunable(): Builder
    {
        return static::withoutGlobalScopes()->whereNull('submission_id')->where('created_at', '<', now()->subHours(self::ORPHAN_HOURS));
    }

    /** The upload an answer's file content refers to ("upload:{hash}"), or null when it refers to none. */
    public static function hashFrom(mixed $content): ?string
    {
        return is_string($content) && preg_match('/^upload:([0-9a-f]{20})$/', $content, $match) ? $match[1] : null;
    }

    /** How answers refer to it. */
    public function reference(): string
    {
        return self::PREFIX.$this->hash;
    }

    /** The storage it's kept in. */
    public function storage(): FilesystemAdapter
    {
        return FileStorage::disk($this->disk);
    }

    /** The form it was uploaded to. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** The response it was sent with. */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
