<?php

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function makeNotification(User $user, array $data = [], ?string $readAt = null): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Auth\\PasswordChangedNotification',
        'data' => array_merge(['title' => 'Test notification', 'body' => 'Something happened', 'url' => '/home'], $data),
        'read_at' => $readAt,
    ]);
}

describe('notification bell', function () {
    it('shows zero unread when there are no notifications', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('notifications.bell')
            ->assertSet('open', false)
            ->assertSee('Notifications')
            ->assertDontSee('Test notification');
    });

    it('counts only unread notifications', function () {
        $user = User::factory()->create();
        makeNotification($user, ['title' => 'Unread one']);
        makeNotification($user, ['title' => 'Unread two']);
        makeNotification($user, ['title' => 'Already read'], readAt: now()->toDateTimeString());
        $this->actingAs($user);

        $component = Volt::test('notifications.bell');

        expect($component->get('unreadCount'))->toBe(2);
    });

    it('lists notifications only for the authenticated user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        makeNotification($user, ['title' => 'Mine']);
        makeNotification($other, ['title' => 'Not mine']);
        $this->actingAs($user);

        Volt::test('notifications.bell')
            ->set('open', true)
            ->assertSee('Mine')
            ->assertDontSee('Not mine');
    });

    it('marks a single notification as read', function () {
        $user = User::factory()->create();
        $notification = makeNotification($user);
        $this->actingAs($user);

        Volt::test('notifications.bell')
            ->call('markAsRead', $notification->id)
            ->assertHasNoErrors();

        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    it('marks all notifications as read', function () {
        $user = User::factory()->create();
        makeNotification($user);
        makeNotification($user);
        $this->actingAs($user);

        Volt::test('notifications.bell')
            ->call('markAllAsRead')
            ->assertHasNoErrors();

        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('cannot mark another user\'s notification as read', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = makeNotification($other);
        $this->actingAs($user);

        expect(fn () => Volt::test('notifications.bell')->call('markAsRead', $notification->id))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('security notifications also write to the bell', function () {
    it('writes a database notification when the password is changed', function () {
        $user = User::factory()->create();

        $user->notify(new PasswordChangedNotification);

        expect($user->fresh()->unreadNotifications()->count())->toBe(1);
        $data = $user->fresh()->unreadNotifications()->first()->data;
        expect($data['title'])->toBe('Password changed');
    });
});
