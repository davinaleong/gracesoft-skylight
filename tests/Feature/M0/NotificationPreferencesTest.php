<?php

use App\Features\M0Foundations;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Notifications\Card\CardDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function userWithDueAndOverdueCards(): User
{
    $user = User::factory()->create();
    $board = Board::factory()->create(['user_id' => $user->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);

    Card::factory()->create(['column_id' => $column->id, 'ends_at' => now()->setTime(17, 0)]);
    Card::factory()->create(['column_id' => $column->id, 'ends_at' => now()->subDays(2)]);

    return $user;
}

it('shows the preferences on the profile page only when the flag is on', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile'))->assertDontSee('Email notifications');

    Feature::for($user)->activate(M0Foundations::class);

    $this->actingAs($user)->get(route('profile'))->assertSee('Email notifications');
});

it('defaults every preference to on', function () {
    $user = User::factory()->create();
    Feature::for($user)->activate(M0Foundations::class);
    $this->actingAs($user);

    Volt::test('profile.notification-preferences')
        ->assertSet('preferences', ['due_today' => true, 'overdue' => true]);
});

it('saves preferences and ignores unknown keys', function () {
    $user = User::factory()->create();
    Feature::for($user)->activate(M0Foundations::class);
    $this->actingAs($user);

    Volt::test('profile.notification-preferences')
        ->set('preferences', ['due_today' => false, 'overdue' => true, 'is_admin' => true])
        ->call('save')
        ->assertSet('saved', true);

    expect($user->fresh()->notification_preferences)->toBe(['due_today' => false, 'overdue' => true]);
});

it('refuses to save when the flag is off', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.notification-preferences')
        ->set('preferences', ['due_today' => false, 'overdue' => false])
        ->call('save')
        ->assertForbidden();

    expect($user->fresh()->notification_preferences)->toBeNull();
});

it('sends both reminders at 08:00 when both preferences are on', function () {
    Notification::fake();
    $this->travelTo(now()->setTime(8, 0));
    $user = userWithDueAndOverdueCards();

    $this->artisan('app:send-card-due-reminders')->assertSuccessful();

    Notification::assertSentTo($user, CardDueNotification::class, fn ($n) => $n->type === CardDueNotification::TYPE_DUE_TODAY);
    Notification::assertSentTo($user, CardDueNotification::class, fn ($n) => $n->type === CardDueNotification::TYPE_OVERDUE);
});

it('skips each reminder type the user switched off', function (string $off, string $stillSent) {
    Notification::fake();
    $this->travelTo(now()->setTime(8, 0));
    $user = userWithDueAndOverdueCards();
    $user->forceFill(['notification_preferences' => [$off => false]])->save();

    $this->artisan('app:send-card-due-reminders')->assertSuccessful();

    Notification::assertSentTo($user, CardDueNotification::class, fn ($n) => $n->type === $stillSent);
    Notification::assertNotSentTo($user, CardDueNotification::class, fn ($n) => $n->type === $off);
})->with([
    'due today off' => ['due_today', 'overdue'],
    'overdue off' => ['overdue', 'due_today'],
]);
