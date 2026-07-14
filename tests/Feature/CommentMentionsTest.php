<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Card\CommentMentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function cardOnWorkspaceBoard(User $owner): Card
{
    $board = Board::factory()->create(['user_id' => $owner->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);

    return Card::factory()->create(['column_id' => $column->id]);
}

describe('mentionHandle', function () {
    it('slugs the user name with no separator', function () {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);

        expect($user->mentionHandle())->toBe('adalovelace');
    });
});

describe('@mentions in comments', function () {
    it('notifies a mentioned workspace member', function () {
        $owner = User::factory()->create();
        $teammate = User::factory()->create(['name' => 'Grace Hopper']);
        $owner->currentWorkspace()->users()->attach($teammate->id, ['role' => Workspace::ROLE_MEMBER]);
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Hey @gracehopper can you take a look?')
            ->call('addComment')
            ->assertHasNoErrors();

        expect($teammate->fresh()->unreadNotifications()->count())->toBe(1);
        $notification = $teammate->fresh()->unreadNotifications()->first();
        expect($notification->type)->toBe(CommentMentionNotification::class);
    });

    it('does not notify the comment author for a self-mention', function () {
        $owner = User::factory()->create(['name' => 'Ada Lovelace']);
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Note to self @adalovelace')
            ->call('addComment')
            ->assertHasNoErrors();

        expect($owner->fresh()->unreadNotifications()->count())->toBe(0);
    });

    it('ignores a handle that does not match any workspace member', function () {
        $owner = User::factory()->create();
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Hey @nobodyhere check this out')
            ->call('addComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', ['body' => 'Hey @nobodyhere check this out']);
    });

    it('is case-insensitive when matching handles', function () {
        $owner = User::factory()->create();
        $teammate = User::factory()->create(['name' => 'Grace Hopper']);
        $owner->currentWorkspace()->users()->attach($teammate->id, ['role' => Workspace::ROLE_MEMBER]);
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Hey @GraceHopper')
            ->call('addComment');

        expect($teammate->fresh()->unreadNotifications()->count())->toBe(1);
    });

    it('notifies each distinct mentioned member only once even if mentioned twice', function () {
        $owner = User::factory()->create();
        $teammate = User::factory()->create(['name' => 'Grace Hopper']);
        $owner->currentWorkspace()->users()->attach($teammate->id, ['role' => Workspace::ROLE_MEMBER]);
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', '@gracehopper are you there @gracehopper?')
            ->call('addComment');

        expect($teammate->fresh()->unreadNotifications()->count())->toBe(1);
    });

    it('renders a recognized mention as a highlighted span with the real name', function () {
        $owner = User::factory()->create();
        $teammate = User::factory()->create(['name' => 'Grace Hopper']);
        $owner->currentWorkspace()->users()->attach($teammate->id, ['role' => Workspace::ROLE_MEMBER]);
        $card = cardOnWorkspaceBoard($owner);
        $this->actingAs($owner);

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Hey @gracehopper')
            ->call('addComment');

        Volt::test('cards.detail', ['card' => $card])
            ->assertSee('Grace Hopper', escape: false);
    });
});
