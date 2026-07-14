<?php

use App\Models\User;
use App\Services\DemoBoardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('demo board on registration', function () {
    it('creates a welcome demo board when a user registers with email/password', function () {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'ada@example.com')->firstOrFail();

        expect($user->boards)->toHaveCount(1);
        expect($user->boards->first()->name)->toContain('Welcome');
        expect($user->boards->first()->columns)->toHaveCount(3);
    });

    it('creates a demo board for a brand-new oauth user', function () {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        $user = User::where('email', 'grace@example.com')->firstOrFail();

        expect($user->boards)->toHaveCount(1);
    });

    it('does not create a second demo board when an oauth login links to an existing account', function () {
        $user = User::factory()->create(['email' => 'linked@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-789',
            'email' => 'linked@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        expect($user->fresh()->boards)->toHaveCount(0);
    });

    it('does not create a demo board for factory-created users (only real signups)', function () {
        $user = User::factory()->create();

        expect($user->boards)->toHaveCount(0);
    });
});

describe('DemoBoardSeeder', function () {
    it('seeds three columns and four cards demonstrating core features', function () {
        $user = User::factory()->create();

        $board = DemoBoardSeeder::seed($user);

        expect($board->workspace_id)->toBe($user->currentWorkspace()->id);
        expect($board->columns)->toHaveCount(3);

        $cardCount = $board->columns->flatMap->cards->count();
        expect($cardCount)->toBe(4);

        $welcomeCard = $board->columns->first()->cards->first();
        expect($welcomeCard->checklists)->toHaveCount(1);
        expect($welcomeCard->checklists->first()->items)->toHaveCount(2);
    });
});
