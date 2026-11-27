<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\VisualizationTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisualizationTab>
 */
class VisualizationTabFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'workspace_id' => fn (array $attributes) => Form::withoutGlobalScope('workspace')->find($attributes['form_id'])->workspace_id,
            'name' => ucfirst(fake()->words(2, true)),
            'position' => 0,
        ];
    }

    /**
     * A tab of the given form, i.e. VisualizationTab::factory()->of($form).
     */
    public function of(Form $form): static
    {
        return $this->state(fn (array $attributes) => ['form_id' => $form->id, 'workspace_id' => $form->workspace_id]);
    }
}
