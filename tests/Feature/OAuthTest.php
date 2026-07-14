<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('oauth redirect', function () {
    it('redirects to the provider for google and github', function (string $provider) {
        Socialite::fake($provider);

        $this->get(route('oauth.redirect', $provider))
            ->assertRedirect();
    })->with(['google', 'github']);

    it('rejects an unsupported provider', function () {
        $this->get('/auth/facebook/redirect')->assertNotFound();
    });

    it('does not allow an authenticated user to hit the redirect route', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('oauth.redirect', 'google'))
            ->assertRedirect(route('home'));
    });
});

describe('oauth callback', function () {
    it('creates a new verified user on first sign-in and logs them in', function () {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-123',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'oauth_provider' => 'google',
            'oauth_provider_id' => 'g-123',
        ]);

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        expect($user->hasVerifiedEmail())->toBeTrue();
        expect($user->password)->toBeNull();

        $this->assertAuthenticatedAs($user);
    });

    it('logs in an existing user matched by provider id', function () {
        $user = User::factory()->create([
            'oauth_provider' => 'github',
            'oauth_provider_id' => 'gh-456',
        ]);

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'gh-456',
            'email' => $user->email,
        ]));

        $this->get(route('oauth.callback', 'github'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    });

    it('links an oauth provider to an existing email/password account', function () {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'oauth_provider' => null,
            'oauth_provider_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-789',
            'email' => 'linked@example.com',
        ]));

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->oauth_provider)->toBe('google');
        expect($user->fresh()->oauth_provider_id)->toBe('g-789');
        expect(User::where('email', 'linked@example.com')->count())->toBe(1);
    });

    it('rejects an unsupported provider', function () {
        $this->get('/auth/facebook/callback')->assertNotFound();
    });

    it('redirects to login with an error when the provider exchange fails', function () {
        Socialite::fake('google', function () {
            throw new Exception('provider unreachable');
        });

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('oauth-only accounts', function () {
    it('cannot log in with a password until one is set', function () {
        $user = User::factory()->create([
            'password' => null,
            'oauth_provider' => 'google',
            'oauth_provider_id' => 'g-999',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'anything',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});
