<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\MarkdownNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarkdownNote>
 */
class MarkdownNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, asText: true),
            'content' => fake()->paragraphs(2, asText: true),
        ];
    }
}
