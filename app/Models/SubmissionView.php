<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\SubmissionViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubmissionView
 *
 * A saved view of a form's responses (legacy "saved filter"): a name, a line about it, and the filter rules
 * (App\Support\SubmissionFilters). Shared with everyone who can see the form; whoever saved it, or manages the form,
 * changes or deletes it.
 */
#[Fillable(['name', 'description', 'filters'])]
#[Hidden(['id', 'workspace_id', 'form_id', 'user_id'])]
class SubmissionView extends Model
{
    /** @use HasFactory<SubmissionViewFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    /** The form it's a view of. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** Who saved it. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
