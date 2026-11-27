<?php

namespace Database\Factories;

use App\Enums\SpaceRole;
use App\Models\Space;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(Space::COLORS),
        ];
    }

    /**
     * In the given workspace, i.e. Space::factory()->in($workspace).
     */
    public function in(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => ['workspace_id' => $workspace->id]);
    }

    /**
     * With the user as a member in the role, i.e. Space::factory()->withMember($user, SpaceRole::Editor).
     */
    public function withMember(User $user, SpaceRole $role = SpaceRole::Viewer): static
    {
        return $this->afterCreating(fn (Space $space) => $space->addMember($user, $role));
    }
}
