<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('shows Zapier and Make.com integration docs on the integrations page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('workspaces.integrations')
        ->assertSee('Zapier & Make.com')
        ->assertSee('Catch Hook')
        ->assertSee('X-Skylight-Signature')
        ->assertSee(route('webhooks'), false)
        ->assertSee(route('profile'), false)
        ->assertSee(url('/api/v1'), false);
});
