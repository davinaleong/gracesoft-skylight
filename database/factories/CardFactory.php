<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.6)->paragraph(),
            'starts_at' => fake()->optional(0.3)->dateTimeBetween('now', '+1 month'),
            'ends_at' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+3 months'),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
