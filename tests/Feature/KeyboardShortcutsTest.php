<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('keyboard shortcuts', function () {
    it('renders the global-search-input id for the / shortcut to target', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="global-search-input"', false);
    });

    it('renders the shortcuts help overlay on every authenticated page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Keyboard shortcuts')
            ->assertSee('Focus search');
    });

    it('renders the quick-add-card keydown handler on the board page', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('boards.show', $board))
            ->assertOk()
            ->assertSee("\$event.key === 'c'", false);
    });
});
