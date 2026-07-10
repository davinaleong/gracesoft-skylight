<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function cardForUser(User $user): Card
{
    $board = Board::factory()->create(['user_id' => $user->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);

    return Card::factory()->create(['column_id' => $column->id]);
}

describe('comments', function () {
    it('posts a comment on a card', function () {
        $user = User::factory()->create();
        $card = cardForUser($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'This is a great card!')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'card_id' => $card->id,
            'user_id' => $user->id,
            'body' => 'This is a great card!',
        ]);
    });

    it('requires comment body', function () {
        $user = User::factory()->create();
        $card = cardForUser($user);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', '')
            ->call('addComment')
            ->assertHasErrors(['newCommentBody']);
    });

    it('deletes own comment', function () {
        $user = User::factory()->create();
        $card = cardForUser($user);
        $comment = Comment::factory()->create(['card_id' => $card->id, 'user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('cards.detail', ['card' => $card])
            ->call('deleteComment', $comment->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($comment);
    });

    it('cannot delete another users comment', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $card = cardForUser($user);
        $comment = Comment::factory()->create(['card_id' => $card->id, 'user_id' => $other->id]);
        $this->actingAs($user);

        expect(fn () => Volt::test('cards.detail', ['card' => $card])
            ->call('deleteComment', $comment->id)
        )->toThrow(ModelNotFoundException::class);

        $this->assertModelExists($comment);
    });
});
