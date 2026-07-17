<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('shows an empty state with no tokens', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-tokens')->assertSee('No API tokens yet.');
});

it('creates a token and reveals the plaintext value once', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('profile.api-tokens')
        ->set('tokenName', 'CLI script')
        ->call('create');

    expect($user->tokens()->where('name', 'CLI script')->exists())->toBeTrue();
    expect($component->get('plainTextToken'))->not->toBeNull();
    $component->assertSee('CLI script');
});

it('revokes a token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Old script');
    $this->actingAs($user);

    Volt::test('profile.api-tokens')->call('revoke', $token->accessToken->id);

    expect($user->tokens()->where('name', 'Old script')->exists())->toBeFalse();
});
