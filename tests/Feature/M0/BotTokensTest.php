<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('creates a read-only bot token, shown once, listed by name with last-used time', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('profile.api-tokens')
        ->set('tokenName', 'Telegram bot')
        ->set('tokenType', 'bot')
        ->call('create')
        ->assertHasNoErrors();

    $token = $user->tokens()->firstOrFail();
    expect($token->abilities)->toBe(['bot:read']);
    expect($component->get('plainTextToken'))->toStartWith("{$token->id}|");

    $component->call('dismissToken')
        ->assertSet('plainTextToken', null)
        ->assertSee('Telegram bot')
        ->assertSee('Read-only bot')
        ->assertSee('never used')
        ->assertDontSee($token->token);
});

it('rejects an unknown token type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-tokens')
        ->set('tokenName', 'Sneaky')
        ->set('tokenType', 'admin')
        ->call('create')
        ->assertHasErrors(['tokenType']);

    expect($user->tokens()->count())->toBe(0);
});

it('lets a bot token read the bot API but not the REST API', function () {
    $user = User::factory()->create();
    $token = $user->createToken('bot', User::API_TOKEN_TYPES['bot']['abilities'])->plainTextToken;

    $this->withToken($token)->getJson('/api/bot/cards/due')->assertOk();
    $this->withToken($token)->getJson('/api/v1/boards')->assertForbidden();
    $this->withToken($token)->postJson('/api/v1/boards', ['name' => 'Nope'])->assertForbidden();
});

it('keeps full-access tokens working on both APIs', function () {
    $user = User::factory()->create();
    $token = $user->createToken('script')->plainTextToken;

    $this->withToken($token)->getJson('/api/bot/cards/due')->assertOk();
    $this->withToken($token)->getJson('/api/v1/boards')->assertOk();
});

it('returns 401 on the next request after a token is revoked', function () {
    $user = User::factory()->create();
    $newToken = $user->createToken('bot', ['bot:read']);

    $this->withToken($newToken->plainTextToken)->getJson('/api/bot/cards/due')->assertOk();

    $this->actingAs($user);
    Volt::test('profile.api-tokens')->call('revoke', $newToken->accessToken->id);

    // Reset the cached guard so the next request re-authenticates from scratch.
    $this->app['auth']->forgetGuards();

    $this->withToken($newToken->plainTextToken)->getJson('/api/bot/cards/due')->assertUnauthorized();
});

it('rate-limits the bot API to 60 requests a minute per token with Retry-After', function () {
    $user = User::factory()->create();
    $limitedToken = $user->createToken('bot one', ['bot:read'])->plainTextToken;
    $otherToken = $user->createToken('bot two', ['bot:read'])->plainTextToken;

    for ($i = 0; $i < 60; $i++) {
        $this->withToken($limitedToken)->getJson('/api/bot/cards/due')->assertOk();
    }

    $this->withToken($limitedToken)->getJson('/api/bot/cards/due')
        ->assertStatus(429)
        ->assertHeader('Retry-After');

    // Limits are per token, so the owner's other token is unaffected.
    $this->app['auth']->forgetGuards();
    $this->withToken($otherToken)->getJson('/api/bot/cards/due')->assertOk();
});
