<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('mobile responsiveness', function () {
    it('lets the nav bar wrap instead of forcing a single row', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('flex flex-wrap items-center gap-3 py-2.5 sm:flex-nowrap', false);
    });

    it('lets the board header and its button group wrap on narrow screens', function () {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('boards.show', $board));

        $response->assertOk()
            ->assertSee('flex flex-wrap items-center justify-between gap-y-3 mb-6', false)
            ->assertSee('flex flex-wrap items-center gap-2', false);
    });
});
