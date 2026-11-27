<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\VisualizationTabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisualizationTab
 *
 * A named sheet on a form's Visualize page, holding its own charts (VisualizationChart) in its own order. Everyone who
 * can see the form sees its tabs; those who can edit the form change them.
 */
#[Fillable(['name'])]
#[Hidden(['id', 'workspace_id', 'form_id'])]
class VisualizationTab extends Model
{
    /** @use HasFactory<VisualizationTabFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** The form it's a tab of. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** Its charts, in order. */
    public function charts(): HasMany
    {
        return $this->hasMany(VisualizationChart::class, 'tab_id')->orderBy('position')->orderBy('id');
    }
}
