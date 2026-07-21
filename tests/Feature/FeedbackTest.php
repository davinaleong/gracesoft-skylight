<?php

use App\Models\User;
use App\Notifications\FeedbackReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('is reachable without authentication', function () {
    $this->get(route('feedback'))->assertOk();
});

it('prefills name and email for an authenticated user', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $this->actingAs($user);

    Volt::test('feedback')
        ->assertSet('name', 'Ada Lovelace')
        ->assertSet('email', 'ada@example.com');
});

it('leaves name and email blank for a guest', function () {
    Volt::test('feedback')
        ->assertSet('name', '')
        ->assertSet('email', '');
});

it('sends feedback to the configured address and lets the sender reply-to', function () {
    Notification::fake();

    Volt::test('feedback')
        ->set('name', 'Grace Hopper')
        ->set('email', 'grace@example.com')
        ->set('message', 'Love the Client Portal feature.')
        ->call('send')
        ->assertSet('sent', true);

    Notification::assertSentOnDemand(
        FeedbackReceivedNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === config('mail.feedback_address')
                && $notification->fromEmail === 'grace@example.com'
                && $notification->message === 'Love the Client Portal feature.';
        }
    );
});

it('requires name, email, and message', function () {
    Volt::test('feedback')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('message', '')
        ->call('send')
        ->assertHasErrors(['name', 'email', 'message']);
});

it('rate-limits repeated submissions from the same session', function () {
    Notification::fake();

    for ($i = 1; $i <= 5; $i++) {
        Volt::test('feedback')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('message', "Attempt {$i}")
            ->call('send')
            ->assertSet('sent', true);
    }

    Volt::test('feedback')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('message', 'Attempt 6')
        ->call('send')
        ->assertSet('sent', false)
        ->assertHasErrors('message');

    Notification::assertSentOnDemandTimes(FeedbackReceivedNotification::class, 5);
});
