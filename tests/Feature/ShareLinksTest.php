<?php

use App\Models\Board;
use App\Models\BoardShareLink;
use App\Models\Card;
use App\Models\Column;
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

    it('does not find an expired link', function () {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $board->shareLinks()->create(['token_hash' => $hash, 'expires_at' => now()->subDay()]);

        expect(BoardShareLink::findByToken($token))->toBeNull();
    });

    it('finds a link that has not expired yet', function () {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $link = $board->shareLinks()->create(['token_hash' => $hash, 'expires_at' => now()->addDay()]);

        expect(BoardShareLink::findByToken($token)?->id)->toBe($link->id);
    });

    it('reports isExpired/isActive correctly', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $expired = $board->shareLinks()->create(['token_hash' => 'a', 'expires_at' => now()->subDay()]);
        $future = $board->shareLinks()->create(['token_hash' => 'b', 'expires_at' => now()->addDay()]);
        $never = $board->shareLinks()->create(['token_hash' => 'c', 'expires_at' => null]);

        expect($expired->isExpired())->toBeTrue();
        expect($expired->isActive())->toBeFalse();
        expect($future->isExpired())->toBeFalse();
        expect($future->isActive())->toBeTrue();
        expect($never->isExpired())->toBeFalse();
        expect($never->isActive())->toBeTrue();
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

    it('generates a link with a 30-day expiry when selected', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.share-links', ['board' => $board])
            ->set('expiresIn', '30')
            ->call('generate')
            ->assertHasNoErrors();

        $link = $board->shareLinks()->latest()->first();
        expect($link->expires_at->isBetween(now()->addDays(29), now()->addDays(31)))->toBeTrue();
    });

    it('generates a link that never expires by default', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Volt::test('boards.share-links', ['board' => $board])
            ->call('generate')
            ->assertHasNoErrors();

        expect($board->shareLinks()->latest()->first()->expires_at)->toBeNull();
    });

    it('shows the view count for each link', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $hash]);
        $this->actingAs($user);

        $this->get(route('viewer', $token));
        $this->get(route('viewer', $token));

        Volt::test('boards.share-links', ['board' => $board])
            ->assertSee('2 views');
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

    it('returns 404 for an expired token', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $hash, 'expires_at' => now()->subHour()]);

        $this->get(route('viewer', $token))->assertNotFound();
    });

    it('shows the client portal branding with the workspace name', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $hash]);

        $this->get(route('viewer', $token))
            ->assertOk()
            ->assertSee('Client Portal')
            ->assertSee($user->currentWorkspace()->name);
    });

    it('hides comments and attachments by default and shows them only when enabled', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id, 'title' => 'Card A']);
        $card->comments()->create(['user_id' => $user->id, 'body' => 'Secret comment']);
        $card->attachments()->create(['user_id' => $user->id, 'type' => 'link', 'path' => 'https://example.com', 'name' => 'Secret link']);

        ['token' => $closedToken, 'hash' => $closedHash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $closedHash, 'can_see_comments' => false, 'can_see_attachments' => false]);

        $this->get(route('viewer', $closedToken))
            ->assertOk()
            ->assertDontSee('Secret comment')
            ->assertDontSee('Secret link');

        ['token' => $openToken, 'hash' => $openHash] = BoardShareLink::generateToken();
        $board->shareLinks()->create(['token_hash' => $openHash, 'can_see_comments' => true, 'can_see_attachments' => true]);

        $this->get(route('viewer', $openToken))
            ->assertOk()
            ->assertSee('Secret comment')
            ->assertSee('Secret link');
    });
});
