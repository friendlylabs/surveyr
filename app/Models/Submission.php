<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use App\Support\AttachmentsExport;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Submission
 *
 * One response to a form, as sent by the public form (Public\FormsController): the answers keyed by question name, as
 * SurveyJS gives them, and a little about how they came in (meta). collected_by is the staff member who filled it in
 * for someone (signed in, in the form's workspace); null when the respondent filled it in themselves. ip_address is
 * where it was sent from, shown to the form's team only. number is its place in the form (1, 2, 3 ...), shown as its
 * reference (RSP-12). Responses are archived out of the list, not deleted; only purging the form's responses deletes
 * them. Shown as "responses" in the app.
 */
#[Fillable(['data', 'meta'])]
#[Hidden(['id', 'workspace_id', 'form_id', 'collected_by', 'ip_address', 'legacy_key'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'meta' => 'array',
            'number' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /** Numbers a new response after the form's last one (saved in a transaction that locks the form's row, so two at once don't clash). */
    protected static function booted(): void
    {
        static::creating(function (Submission $submission) {
            $submission->number ??= (int) static::withoutGlobalScopes()->where('form_id', $submission->form_id)->max('number') + 1;
        });
    }

    /** How people refer to it: RSP-12. */
    protected function reference(): Attribute
    {
        return Attribute::get(fn () => 'RSP-'.$this->number);
    }

    /** Responses in the list (not archived). */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** Archived responses. */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /** The staff member who collected it, if one did. */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /** The files sent with it, kept in storage. */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class)->withoutGlobalScopes();
    }

    /** The form answered. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * One file of a question's answer, or null when there's no such file or it isn't held here. A file question's answer
     * is [{name, type, content}], its content an upload kept in storage ("upload:{hash}", then `upload` is given) or,
     * in older responses, the file itself as a data URL (then `bytes`); a signature pad's is a data URL (index 0).
     *
     * @return array{name: string, type: string, bytes?: string, upload?: Upload}|null
     */
    public function file(string $question, int $index): ?array
    {
        $answer = $this->data[$question] ?? null;
        $file = is_string($answer) ? ($index === 0 ? ['content' => $answer] : null) : (is_array($answer) ? ($answer[$index] ?? null) : null);
        if (! is_array($file)) {
            return null;
        }

        $hash = Upload::hashFrom($file['content'] ?? null);
        if ($hash !== null) {
            $upload = $this->uploads()->where('hash', $hash)->first();

            return $upload ? ['name' => $upload->name, 'type' => $upload->type, 'upload' => $upload] : null;
        }

        $decoded = is_string($file['content'] ?? null) ? AttachmentsExport::decode($file['content']) : null;
        if (! $decoded) {
            return null;
        }

        $name = is_string($file['name'] ?? null) && $file['name'] !== ''
            ? $file['name']
            : $question.'.'.(AttachmentsExport::EXTENSIONS[$decoded['type']] ?? 'bin');

        return ['name' => $name, 'type' => $decoded['type'], 'bytes' => $decoded['bytes']];
    }
}
