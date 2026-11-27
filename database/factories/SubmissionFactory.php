<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
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
            'data' => ['village' => fake()->city(), 'source' => fake()->randomElement(['borehole', 'river', 'piped'])],
            'meta' => ['locale' => 'en'],
        ];
    }

    /**
     * A response to the given form, i.e. Submission::factory()->to($form).
     */
    public function to(Form $form): static
    {
        return $this->state(fn (array $attributes) => ['form_id' => $form->id, 'workspace_id' => $form->workspace_id]);
    }
}
