<?php

use App\Features\M0Foundations;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

it('is off by default', function () {
    $user = User::factory()->create();

    expect(Feature::for($user)->active(M0Foundations::class))->toBeFalse();
});

it('is on for early-access emails only', function () {
    config(['features.m0_foundations.early_access' => ['early@example.com']]);

    $earlyUser = User::factory()->create(['email' => 'Early@Example.com']);
    $otherUser = User::factory()->create();

    expect(Feature::for($earlyUser)->active(M0Foundations::class))->toBeTrue();
    expect(Feature::for($otherUser)->active(M0Foundations::class))->toBeFalse();
});

it('is on for everyone when enabled', function () {
    config(['features.m0_foundations.enabled' => true]);

    $user = User::factory()->create();

    expect(Feature::for($user)->active(M0Foundations::class))->toBeTrue();
});
