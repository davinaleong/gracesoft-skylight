<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bot boards summary endpoint returns card counts per column, per board for a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $board = Board::factory()->for($user)->create(['name' => 'Launch Plan', 'position' => 0]);
    $todo = Column::factory()->for($board)->create(['name' => 'To Do', 'position' => 0]);
    $done = Column::factory()->for($board)->create(['name' => 'Done', 'position' => 1]);

    Card::factory()->for($todo)->count(2)->create();
    Card::factory()->for($done)->count(1)->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/boards/summary')
        ->assertOk()
        ->assertJson([
            'boards' => [
                [
                    'board' => 'Launch Plan',
                    'columns' => [
                        ['column' => 'To Do', 'card_count' => 2],
                        ['column' => 'Done', 'card_count' => 1],
                    ],
                ],
            ],
        ]);
});

test('bot boards summary endpoint only returns the token owner\'s own boards', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    Board::factory()->for($otherUser)->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/boards/summary')
        ->assertOk()
        ->assertJson(['boards' => []]);
});

test('bot boards summary endpoint rejects requests without a valid token', function () {
    $this->getJson('/api/bot/boards/summary')->assertUnauthorized();
});

test('bot boards summary endpoint returns an empty list when there are no boards', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/boards/summary')
        ->assertOk()
        ->assertJson(['boards' => []]);
});
