<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bot cards due endpoint returns due-today and overdue cards for a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $board = Board::factory()->for($user)->create(['name' => 'Launch Plan']);
    $column = Column::factory()->for($board)->create(['name' => 'In Progress']);

    Card::factory()->for($column)->create([
        'title' => 'Due today card',
        'ends_at' => now(),
    ]);
    Card::factory()->for($column)->create([
        'title' => 'Overdue card',
        'ends_at' => now()->subDays(2),
    ]);
    Card::factory()->for($column)->create([
        'title' => 'Future card',
        'ends_at' => now()->addDays(5),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/cards/due')
        ->assertOk()
        ->assertJsonCount(1, 'due_today')
        ->assertJsonCount(1, 'overdue')
        ->assertJsonPath('due_today.0.title', 'Due today card')
        ->assertJsonPath('due_today.0.board', 'Launch Plan')
        ->assertJsonPath('overdue.0.title', 'Overdue card');
});

test('bot cards due endpoint only returns the token owner\'s own cards', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $otherBoard = Board::factory()->for($otherUser)->create();
    $otherColumn = Column::factory()->for($otherBoard)->create();
    Card::factory()->for($otherColumn)->create(['ends_at' => now()]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/cards/due')
        ->assertOk()
        ->assertJsonCount(0, 'due_today')
        ->assertJsonCount(0, 'overdue');
});

test('bot cards due endpoint rejects requests without a valid token', function () {
    $this->getJson('/api/bot/cards/due')->assertUnauthorized();
});

test('bot cards due endpoint returns empty lists when there are no cards', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/cards/due')
        ->assertOk()
        ->assertJson(['due_today' => [], 'overdue' => []]);
});
