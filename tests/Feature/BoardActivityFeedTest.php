<?php

use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('board activity feed', function () {
    it('shows board and card events for that board', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        Card::factory()->create(['column_id' => $column->id, 'title' => 'Ship the feature']);

        Volt::test('boards.activity', ['board' => $board])
            ->assertSee('created this board')
            ->assertSee('created card "Ship the feature"');
    });

    it('does not show activity from a different board', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $boardA = Board::factory()->create(['user_id' => $user->id, 'name' => 'Board A']);
        $boardB = Board::factory()->create(['user_id' => $user->id, 'name' => 'Board B']);
        $columnB = Column::factory()->create(['board_id' => $boardB->id]);
        Card::factory()->create(['column_id' => $columnB->id, 'title' => 'Only on B']);

        Volt::test('boards.activity', ['board' => $boardA])
            ->assertDontSee('Only on B');
    });

    it('still shows a deleted card in the feed via the stored board_id', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id, 'title' => 'Temporary card']);
        $card->delete();

        Volt::test('boards.activity', ['board' => $board])
            ->assertSee('deleted card "Temporary card"');
    });

    it('describes a plain card field update without crashing on the diff values', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id, 'title' => 'Original title']);
        $card->update(['description' => 'New description']);

        Volt::test('boards.activity', ['board' => $board])
            ->assertSee('updated card "Original title" (description)');
    });

    it('describes a card move by destination column name', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $col1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $col2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);
        $card = Card::factory()->create(['column_id' => $col1->id, 'title' => 'Ship it']);

        Volt::test('boards.show', ['board' => $board])
            ->call('moveCard', $card->id, $col2->id, 0);

        Volt::test('boards.activity', ['board' => $board])
            ->assertSee('moved card "Ship it" to Done');
    });

    it('shows no activity yet for a brand new board with no card events', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Suppress the board.created event this factory would trigger by inserting raw.
        $board = Board::factory()->create(['user_id' => $user->id]);
        ActivityLog::query()->delete();

        Volt::test('boards.activity', ['board' => $board])
            ->assertSee('No activity yet.');
    });
});
