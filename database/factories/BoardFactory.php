<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, asText: true),
            'description' => fake()->optional(0.5)->sentence(),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
