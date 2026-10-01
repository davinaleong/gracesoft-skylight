<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('board labels', function () {
    it('creates a label on a board', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('newLabelName', 'Bug')
            ->set('newLabelColor', '#ef4444')
            ->call('createLabel')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('labels', [
            'board_id' => $board->id,
            'name' => 'Bug',
            'color' => '#ef4444',
        ]);
    });

    it('validates label name is required', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->set('newLabelName', '')
            ->call('createLabel')
            ->assertHasErrors(['newLabelName']);
    });

    it('deletes a label', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $label = Label::factory()->create(['board_id' => $board->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('deleteLabel', $label->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($label);
    });
});

describe('card labels', function () {
    it('attaches a label to a card', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $label = Label::factory()->create(['board_id' => $board->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('toggleCardLabel', $card->id, $label->id)
            ->assertHasNoErrors();

        expect($card->fresh()->labels->contains($label))->toBeTrue();
    });

    it('detaches a label from a card when toggled again', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $label = Label::factory()->create(['board_id' => $board->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $card->labels()->attach($label);
        $this->actingAs($user);

        Volt::test('boards.show', ['board' => $board])
            ->call('toggleCardLabel', $card->id, $label->id)
            ->assertHasNoErrors();

        expect($card->fresh()->labels->contains($label))->toBeFalse();
    });
});
