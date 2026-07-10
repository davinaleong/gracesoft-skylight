<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'user_id' => User::factory(),
            'type' => Attachment::TYPE_LINK,
            'path' => fake()->url(),
            'name' => fake()->words(3, asText: true),
            'mime_type' => null,
            'size' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'type' => Attachment::TYPE_IMAGE,
            'path' => 'attachments/'.fake()->uuid().'.jpg',
            'name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50000, 5000000),
        ]);
    }
}
