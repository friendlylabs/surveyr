<?php

namespace Database\Factories;

use App\Models\VisualizationChart;
use App\Models\VisualizationTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisualizationChart>
 */
class VisualizationChartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tab_id' => VisualizationTab::factory(),
            'form_id' => fn (array $attributes) => VisualizationTab::withoutGlobalScope('workspace')->find($attributes['tab_id'])->form_id,
            'workspace_id' => fn (array $attributes) => VisualizationTab::withoutGlobalScope('workspace')->find($attributes['tab_id'])->workspace_id,
            'position' => 0,
            'kind' => 'question',
            'title' => null,
            'rules' => ['question' => 'source', 'visual' => null, 'sort' => 'form', 'limit' => 0],
        ];
    }

    /**
     * A chart on the given tab, i.e. VisualizationChart::factory()->on($tab).
     */
    public function on(VisualizationTab $tab): static
    {
        return $this->state(fn (array $attributes) => ['tab_id' => $tab->id, 'form_id' => $tab->form_id, 'workspace_id' => $tab->workspace_id]);
    }

    /**
     * A chart of one question, with any rules given (visual, sort, limit).
     *
     * @param  array<string, mixed>  $rules
     */
    public function question(string $question, array $rules = []): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => 'question',
            'rules' => ['question' => $question, 'visual' => null, 'sort' => 'form', 'limit' => 0, ...$rules],
        ]);
    }
}
