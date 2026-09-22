<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a bot sanctum token cannot authenticate against session-guarded web routes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    // Web routes (including the Livewire-backed pages where writes happen)
    // are guarded by the session-based `auth` guard, not `sanctum`, so a
    // genuine bot Bearer token must not grant access to them.
    $this->withHeader('Authorization', "Bearer {$token}")
        ->get(route('home'))
        ->assertRedirect(route('login'));
});

test('a bot sanctum token can authenticate against the bot API routes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/bot/boards/summary')
        ->assertOk();
});
