<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('registration', function () {
    it('renders the registration page', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your account');
    });

    it('registers a new user and redirects to home', function () {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    });

    it('fails registration with mismatched passwords', function () {
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    });
});

describe('login', function () {
    it('renders the login page', function () {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to your account');
    });

    it('authenticates a user with correct credentials', function () {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    });

    it('rejects invalid credentials', function () {
        User::factory()->create(['password' => bcrypt('secret')]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('logout', function () {
    it('logs out an authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });
});

describe('password reset', function () {
    it('renders the forgot password page', function () {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send reset link');
    });
});

describe('protected routes', function () {
    it('redirects guests from home to login', function () {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from profile to login', function () {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users to access home', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('My Boards');
    });

    it('allows authenticated users to access profile', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Profile settings');
    });
});
