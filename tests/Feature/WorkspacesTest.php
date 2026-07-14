<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('workspace creation', function () {
    it('creates a personal workspace when a user registers with email/password', function () {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'ada@example.com')->firstOrFail();

        expect($user->workspaces)->toHaveCount(1);

        $workspace = $user->workspaces->first();
        expect($workspace->owner_id)->toBe($user->id);
        expect($workspace->pivot->role)->toBe(Workspace::ROLE_OWNER);
        expect($workspace->name)->toBe("Ada Lovelace's Workspace");
    });

    it('creates a personal workspace for a brand-new oauth user', function () {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        $user = User::where('email', 'grace@example.com')->firstOrFail();

        expect($user->workspaces)->toHaveCount(1);
        expect($user->workspaces->first()->pivot->role)->toBe(Workspace::ROLE_OWNER);
    });

    it('does not create a duplicate workspace when an oauth login links to an existing account', function () {
        $user = User::factory()->create(['email' => 'linked@example.com']);
        $workspace = Workspace::createForUser($user);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-789',
            'email' => 'linked@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        expect($user->fresh()->workspaces)->toHaveCount(1);
        expect($user->fresh()->workspaces->first()->id)->toBe($workspace->id);
    });
});

describe('Workspace::createForUser', function () {
    it('generates a uuid and attaches the owner with the owner role', function () {
        $user = User::factory()->create();

        $workspace = Workspace::createForUser($user, 'Acme Inc');

        expect($workspace->uuid)->not->toBeNull();
        expect($workspace->name)->toBe('Acme Inc');
        expect($workspace->users)->toHaveCount(1);
        expect($workspace->users->first()->id)->toBe($user->id);
        expect($workspace->users->first()->pivot->role)->toBe('owner');
    });

    it('defaults the workspace name based on the user name', function () {
        $user = User::factory()->create(['name' => 'Test User']);

        $workspace = Workspace::createForUser($user);

        expect($workspace->name)->toBe("Test User's Workspace");
    });
});
