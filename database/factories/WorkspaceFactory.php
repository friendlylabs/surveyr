<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    /**
     * Owned by the user, who joins it as Owner, i.e Workspace::factory()->ownedBy($user).
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['owner_id' => $user->id])
            ->afterCreating(fn (Workspace $workspace) => $workspace->addMember($user, WorkspaceRole::Owner));
    }
}
