<?php

use App\Models\Board;
use App\Models\BoardShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('BoardShareLink model', function () {
    it('generates a token and its hash', function () {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();

        expect($token)->toBeString()->not->toBeEmpty();
        expect($hash)->toBe(hash('sha256', $token));
        expect($token)->not->toBe($hash);
    });

    it('finds an active link by raw token', function () {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $link = $board->shareLinks()->create(['token_hash' => $hash]);

        $found = BoardShareLink::findByToken($token);

        expect($found?->id)->toBe($link->id);
    });

    it('does not find a revoked link', function () {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $board->shareLinks()->create(['token_hash' => $hash, 'revoked_at' => now()]);

        expect(BoardShareLink::findByToken($token))->toBeNull();
    });
});

describe('share links Volt component', function () {
    it('generates a new share link', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.share-links', ['board' => $board])
            ->set('canSeeComments', true)
            ->call('generate')
            ->assertHasNoErrors()
            ->assertSet('newlyGeneratedToken', fn ($token) => ! empty($token));

        $this->assertDatabaseHas('board_share_links', [
            'board_id' => $board->id,
            'can_see_comments' => true,
        ]);
    });

    it('revokes a share link', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['hash' => $hash] = BoardShareLink::generateToken();
        $link = $board->shareLinks()->create(['token_hash' => $hash]);
        $this->actingAs($user);

        Volt::test('boards.share-links', ['board' => $board])
            ->call('revoke', $link->id)
            ->assertHasNoErrors();

        expect($link->fresh()->revoked_at)->not->toBeNull();
    });
});

describe('public viewer route', function () {
    it('serves the board view to anyone with a valid token', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $hash]);

        $this->get(route('viewer', $token))
            ->assertOk()
            ->assertSee($board->name)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    });

    it('returns 404 for an unknown token', function () {
        $this->get(route('viewer', 'invalid-token-xyz'))->assertNotFound();
    });

    it('returns 404 for a revoked token', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $hash, 'revoked_at' => now()]);

        $this->get(route('viewer', $token))->assertNotFound();
    });

    it('logs the access', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $link = $board->shareLinks()->create(['token_hash' => $hash]);

        $this->get(route('viewer', $token));

        $this->assertDatabaseHas('share_link_accesses', [
            'board_share_link_id' => $link->id,
        ]);
    });
});
