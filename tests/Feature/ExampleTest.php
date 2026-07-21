<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the root URL shows the marketing landing page to guests', function () {
    $this->get('/')->assertOk()->assertSee('Start free');
});

test('the root URL redirects authenticated users to their dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect(route('home'));
});
