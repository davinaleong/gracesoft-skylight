<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'url' => fake()->url(),
            'secret' => Webhook::generateSecret(),
            'events' => ['card.created'],
            'is_active' => true,
        ];
    }
}
