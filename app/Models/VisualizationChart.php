<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\VisualizationChartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisualizationChart
 *
 * A chart on a Visualize tab: one question's summary (kind question) or a grouped chart (kind grouped), with its rules
 * (visual, sort, top N and what it charts; App\Support\Analytics\ChartRules) and an optional title of its own.
 */
#[Fillable(['kind', 'title', 'rules'])]
#[Hidden(['id', 'workspace_id', 'form_id', 'tab_id'])]
class VisualizationChart extends Model
{
    /** @use HasFactory<VisualizationChartFactory> */
    use BelongsToWorkspace, HasFactory, HasHash;

    /** Kinds of chart. */
    const KINDS = ['question', 'grouped'];

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'position' => 'integer',
        ];
    }

    /** The tab it's on. */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(VisualizationTab::class, 'tab_id');
    }

    /** The form it charts. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
