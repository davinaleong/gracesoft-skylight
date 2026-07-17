<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function apiToken(User $user): string
{
    return $user->createToken('test')->plainTextToken;
}

/**
 * Sanctum's RequestGuard caches the resolved user for the lifetime of the guard
 * instance, which — inside a single Pest test — spans every simulated HTTP call.
 * Forget the guard before switching bearer tokens to a different user mid-test,
 * or the cached identity from the first call leaks into later calls.
 */
function forgetAuthGuards(): void
{
    app('auth')->forgetGuards();
}

describe('authentication', function () {
    it('rejects requests without a token', function () {
        $this->getJson('/api/v1/boards')->assertStatus(401);
    });

    it('rejects requests with an invalid token', function () {
        $this->withToken('bogus-token')->getJson('/api/v1/boards')->assertStatus(401);
    });
});

describe('boards', function () {
    it('lists the authenticated user\'s boards', function () {
        $user = User::factory()->create();
        Board::factory(2)->create(['user_id' => $user->id]);

        $this->withToken(apiToken($user))
            ->getJson('/api/v1/boards')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('shows a single board with nested columns', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        Column::factory(2)->create(['board_id' => $board->id]);

        $this->withToken(apiToken($user))
            ->getJson("/api/v1/boards/{$board->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $board->uuid)
            ->assertJsonCount(2, 'data.columns');
    });

    it('forbids showing a board the token owner has no workspace access to', function () {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $stranger->id]);

        $this->withToken(apiToken($user))
            ->getJson("/api/v1/boards/{$board->uuid}")
            ->assertStatus(403);
    });

    it('creates a board in the token owner\'s current workspace', function () {
        $user = User::factory()->create();

        $this->withToken(apiToken($user))
            ->postJson('/api/v1/boards', ['name' => 'Roadmap', 'description' => 'Q3 plan'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Roadmap');

        expect(Board::where('name', 'Roadmap')->first()->workspace_id)
            ->toBe($user->currentWorkspace()->id);
    });

    it('rejects board creation past the plan limit', function () {
        $user = User::factory()->create();
        $workspace = $user->currentWorkspace();
        Board::factory(3)->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->withToken(apiToken($user))
            ->postJson('/api/v1/boards', ['name' => 'One too many'])
            ->assertStatus(422);
    });

    it('updates a board it owns', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->withToken(apiToken($user))
            ->patchJson("/api/v1/boards/{$board->uuid}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');
    });

    it('deletes a board it owns', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->withToken(apiToken($user))
            ->deleteJson("/api/v1/boards/{$board->uuid}")
            ->assertStatus(204);

        expect(Board::find($board->id))->toBeNull();
    });

    it('lets a viewer read but not create/update/delete', function () {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($viewer->id, ['role' => Workspace::ROLE_VIEWER]);
        $board = Board::factory()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $token = apiToken($viewer);

        $this->withToken($token)->getJson("/api/v1/boards/{$board->uuid}")->assertOk();
        $this->withToken($token)->patchJson("/api/v1/boards/{$board->uuid}", ['name' => 'Nope'])->assertStatus(403);
        $this->withToken($token)->deleteJson("/api/v1/boards/{$board->uuid}")->assertStatus(403);
    });
});

describe('columns', function () {
    it('lists and creates columns on a board', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $token = apiToken($user);

        $this->withToken($token)->getJson("/api/v1/boards/{$board->uuid}/columns")->assertOk()->assertJsonCount(0, 'data');

        $this->withToken($token)
            ->postJson("/api/v1/boards/{$board->uuid}/columns", ['name' => 'To Do'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'To Do');

        $this->withToken($token)->getJson("/api/v1/boards/{$board->uuid}/columns")->assertJsonCount(1, 'data');
    });

    it('updates and deletes a column', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $token = apiToken($user);

        $this->withToken($token)
            ->patchJson("/api/v1/columns/{$column->id}", ['name' => 'Doing'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Doing');

        $this->withToken($token)->deleteJson("/api/v1/columns/{$column->id}")->assertStatus(204);
        expect(Column::find($column->id))->toBeNull();
    });
});

describe('cards', function () {
    it('lists, creates, updates, and deletes cards', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $token = apiToken($user);

        $this->withToken($token)
            ->postJson("/api/v1/columns/{$column->id}/cards", ['title' => 'Ship API'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Ship API');

        $card = Card::where('title', 'Ship API')->firstOrFail();

        $this->withToken($token)->getJson("/api/v1/columns/{$column->id}/cards")->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v1/cards/{$card->id}")->assertOk()->assertJsonPath('data.id', $card->id);

        $this->withToken($token)
            ->patchJson("/api/v1/cards/{$card->id}", ['title' => 'Ship the API'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Ship the API');

        $this->withToken($token)->deleteJson("/api/v1/cards/{$card->id}")->assertStatus(204);
        expect(Card::find($card->id))->toBeNull();
    });
});

describe('comments', function () {
    it('lists and creates comments, and only the author can delete', function () {
        $owner = User::factory()->create();
        $teammate = User::factory()->create();
        $workspace = $owner->currentWorkspace();
        $workspace->users()->attach($teammate->id, ['role' => Workspace::ROLE_MEMBER]);
        $board = Board::factory()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $ownerToken = apiToken($owner);
        $teammateToken = apiToken($teammate);

        $this->withToken($ownerToken)
            ->postJson("/api/v1/cards/{$card->id}/comments", ['body' => 'Looks good'])
            ->assertCreated();

        $comment = $card->comments()->first();

        forgetAuthGuards();
        $this->withToken($teammateToken)->getJson("/api/v1/cards/{$card->id}/comments")->assertJsonCount(1, 'data');

        forgetAuthGuards();
        $this->withToken($teammateToken)->deleteJson("/api/v1/comments/{$comment->id}")->assertStatus(403);

        forgetAuthGuards();
        $this->withToken($ownerToken)->deleteJson("/api/v1/comments/{$comment->id}")->assertStatus(204);
    });
});
