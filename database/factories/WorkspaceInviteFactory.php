<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceInvite>
 */
class WorkspaceInviteFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'role' => Workspace::ROLE_MEMBER,
            'token_hash' => hash('sha256', (string) fake()->uuid()),
            'invited_by' => User::factory(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['accepted_at' => now()]);
    }
}
