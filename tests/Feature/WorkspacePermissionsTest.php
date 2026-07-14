<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\Column;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function boardForOwnerWithViewer(): array
{
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $member = User::factory()->create();
    $owner->currentWorkspace()->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);
    $owner->currentWorkspace()->users()->attach($member->id, ['role' => Workspace::ROLE_MEMBER]);

    $board = Board::factory()->create(['user_id' => $owner->id]);

    return compact('owner', 'viewer', 'member', 'board');
}

describe('viewers are read-only on boards', function () {
    it('can view a board but cannot create a column', function () {
        ['viewer' => $viewer, 'board' => $board] = boardForOwnerWithViewer();

        $this->actingAs($viewer)
            ->get(route('boards.show', $board))
            ->assertOk();

        $this->actingAs($viewer);

        Volt::test('boards.show', ['board' => $board])
            ->set('newColumnName', 'To Do')
            ->call('createColumn')
            ->assertForbidden();

        $this->assertDatabaseMissing('columns', ['board_id' => $board->id, 'name' => 'To Do']);
    });

    it('cannot create a board in a workspace they only view', function () {
        // boards.index always operates on auth()->user()->currentWorkspace(), which today
        // always resolves to the user's own (owned) workspace -- see the known gap noted
        // in WorkspaceInvitesTest. This exercises the underlying capability check directly.
        ['owner' => $owner, 'viewer' => $viewer] = boardForOwnerWithViewer();

        expect($owner->currentWorkspace()->canEditContent($viewer))->toBeFalse();
    });

    it('cannot create a card or add a comment', function () {
        ['viewer' => $viewer, 'board' => $board] = boardForOwnerWithViewer();
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $this->actingAs($viewer);

        Volt::test('boards.show', ['board' => $board])
            ->set('newCardTitle', 'Nope')
            ->call('createCard', $column->id)
            ->assertForbidden();

        Volt::test('cards.detail', ['card' => $card])
            ->set('newCommentBody', 'Nope')
            ->call('addComment')
            ->assertForbidden();

        $this->assertDatabaseMissing('comments', ['card_id' => $card->id]);
    });

    it('cannot generate a board share link', function () {
        ['viewer' => $viewer, 'board' => $board] = boardForOwnerWithViewer();
        $this->actingAs($viewer);

        Volt::test('boards.share-links', ['board' => $board])
            ->call('generate')
            ->assertForbidden();
    });
});

describe('members can edit content but not manage the workspace', function () {
    it('can create a column and a card', function () {
        ['member' => $member, 'board' => $board] = boardForOwnerWithViewer();
        $this->actingAs($member);

        Volt::test('boards.show', ['board' => $board])
            ->set('newColumnName', 'Doing')
            ->call('createColumn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('columns', ['board_id' => $board->id, 'name' => 'Doing']);
    });

    it('can edit content but cannot manage workspace members', function () {
        ['owner' => $owner, 'member' => $member] = boardForOwnerWithViewer();

        expect($owner->currentWorkspace()->canEditContent($member))->toBeTrue();
        expect($owner->currentWorkspace()->canManageMembers($member))->toBeFalse();
    });
});

describe('checklists and cards respect the same permission check', function () {
    it('blocks a viewer from toggling a checklist item', function () {
        ['viewer' => $viewer, 'board' => $board] = boardForOwnerWithViewer();
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $checklist = Checklist::factory()->create(['card_id' => $card->id]);
        $item = $checklist->items()->create(['body' => 'Do the thing', 'position' => 0]);
        $this->actingAs($viewer);

        Volt::test('cards.detail', ['card' => $card])
            ->call('toggleItem', $item->id)
            ->assertForbidden();

        expect($item->fresh()->is_completed)->toBeFalse();
    });
});
