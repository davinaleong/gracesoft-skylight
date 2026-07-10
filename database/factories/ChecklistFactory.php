<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checklist>
 */
class ChecklistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'name' => fake()->words(2, asText: true),
        ];
    }
}
