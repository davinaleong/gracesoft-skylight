<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Label;
use App\Models\Tag;
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

describe('tags', function () {
    it('can create a tag for a user', function () {
        $user = User::factory()->create();

        $tag = $user->tags()->create(['name' => 'urgent', 'color' => '#ef4444']);

        $this->assertDatabaseHas('tags', [
            'user_id' => $user->id,
            'name' => 'urgent',
        ]);

        expect($user->tags)->toHaveCount(1);
        expect($tag->name)->toBe('urgent');
    });

    it('can attach a tag to a board', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $board->tags()->attach($tag);

        expect($board->fresh()->tags)->toHaveCount(1);
        expect($board->tags->first()->id)->toBe($tag->id);
    });

    it('can attach a tag to a column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $column->tags()->attach($tag);

        expect($column->fresh()->tags)->toHaveCount(1);
    });
});
