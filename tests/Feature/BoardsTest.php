<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('boards index', function () {
    it('shows the boards list for authenticated user', function () {
        $user = User::factory()->create();
        Board::factory(3)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Volt::test('boards.index')
            ->assertSee($user->boards->first()->name);
    });

    it('creates a new board', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'New Project')
            ->set('description', 'My new project board')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('boards', [
            'user_id' => $user->id,
            'name' => 'New Project',
        ]);
    });

    it('validates board name is required', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', '')
            ->call('create')
            ->assertHasErrors(['name']);
    });

    it('deletes a board', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.index')
            ->call('delete', $board->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($board);
    });
});

describe('boards show', function () {
    it('shows the board to its owner', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('boards.show', $board))
            ->assertOk()
            ->assertSee($board->name);
    });

    it('forbids access by another user', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('boards.show', $board))
            ->assertForbidden();
    });

    it('creates a column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('newColumnName', 'To Do')
            ->call('createColumn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('columns', [
            'board_id' => $board->id,
            'name' => 'To Do',
        ]);
    });

    it('deletes a column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('deleteColumn', $column->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($column);
    });

    it('creates a card', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('newCardTitle', 'Do the thing')
            ->call('createCard', $column->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cards', [
            'column_id' => $column->id,
            'title' => 'Do the thing',
        ]);
    });

    it('deletes a card', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('deleteCard', $card->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($card);
    });

    it('moves a card to another column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $col1 = Column::factory()->create(['board_id' => $board->id, 'position' => 0]);
        $col2 = Column::factory()->create(['board_id' => $board->id, 'position' => 1]);
        $card = Card::factory()->create(['column_id' => $col1->id, 'position' => 0]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('moveCard', $card->id, $col2->id, 0)
            ->assertHasNoErrors();

        expect($card->fresh()->column_id)->toBe($col2->id);
    });
});
