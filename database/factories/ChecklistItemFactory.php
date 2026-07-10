<?php

namespace Database\Factories;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checklist_id' => Checklist::factory(),
            'body' => fake()->sentence(4),
            'is_completed' => fake()->boolean(20),
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
