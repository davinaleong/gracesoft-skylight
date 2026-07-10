<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, asText: true),
            'description' => fake()->optional(0.5)->sentence(),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
