<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'workspace_id' => Workspace::factory(),
            'title' => rtrim($title, '.'),
            'description' => fake()->sentence(),
            'content' => ['title' => rtrim($title, '.'), 'pages' => [['name' => 'page1', 'elements' => []]]],
        ];
    }

    /**
     * In the given workspace, i.e. Form::factory()->in($workspace).
     */
    public function in(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => ['workspace_id' => $workspace->id]);
    }

    /**
     * Owned by the user, i.e. Form::factory()->ownedBy($user).
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
    }
}
