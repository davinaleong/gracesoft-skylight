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

    it('shows an empty state with a create-board CTA when there are no boards', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->assertSee('No boards yet')
            ->assertSee('Create a board');
    });

    it('creates a blank board with no columns by default', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'Blank Project')
            ->call('create')
            ->assertHasNoErrors();

        $board = Board::where('name', 'Blank Project')->firstOrFail();
        expect($board->columns)->toHaveCount(0);
    });

    it('creates a board pre-populated with a template\'s columns', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'Sprint 1')
            ->set('template', 'sprint')
            ->call('create')
            ->assertHasNoErrors();

        $board = Board::where('name', 'Sprint 1')->firstOrFail();
        expect($board->columns->pluck('name')->all())
            ->toBe(['Backlog', 'To Do', 'In Progress', 'Review', 'Done']);
    });

    it('rejects an unknown template key', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('boards.index')
            ->set('name', 'Sprint 1')
            ->set('template', 'not-a-real-template')
            ->call('create')
            ->assertHasErrors(['template']);
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

    it('shows an empty state with an add-column CTA when the board has no columns', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->assertSee('No columns yet')
            ->assertSee('Add a column');
    });

    it('shows a no-cards message inside an empty column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->assertSee('No cards yet.')
            ->assertDontSee('No columns yet');
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
            ->call('moveCard', $card->id, $col2->id, [$card->id])
            ->assertHasNoErrors();

        expect($card->fresh()->column_id)->toBe($col2->id);
    });

    it('repositions the rest of the destination column in one round-trip', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $col1 = Column::factory()->create(['board_id' => $board->id, 'position' => 0]);
        $col2 = Column::factory()->create(['board_id' => $board->id, 'position' => 1]);
        $moving = Card::factory()->create(['column_id' => $col1->id, 'position' => 0]);
        $existingA = Card::factory()->create(['column_id' => $col2->id, 'position' => 0]);
        $existingB = Card::factory()->create(['column_id' => $col2->id, 'position' => 1]);
        $this->actingAs($user);

        // Dropped in the middle: existingA, moving, existingB
        Volt::test('boards.show', ['board' => $board])
            ->call('moveCard', $moving->id, $col2->id, [$existingA->id, $moving->id, $existingB->id])
            ->assertHasNoErrors();

        expect($moving->fresh())->column_id->toBe($col2->id)->position->toBe(1);
        expect($existingA->fresh()->position)->toBe(0);
        expect($existingB->fresh()->position)->toBe(2);
    });
});
