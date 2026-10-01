<?php

use App\Features\M0Foundations;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Account\AccountDeletionScheduledNotification;
use App\Services\AccountDeletion;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Fortify;
use Laravel\Pennant\Feature;
use Livewire\Volt\Volt;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

function deletionUser(array $attributes = []): User
{
    $user = User::factory()->create(['password' => 'secret-password', ...$attributes]);
    Feature::for($user)->activate(M0Foundations::class);

    return $user;
}

/**
 * @return string The TOTP secret.
 */
function enableTwoFactor(User $user): string
{
    $secret = app(Google2FA::class)->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-one', 'recovery-two'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $secret;
}

it('schedules deletion 7 days out with the right password and emails a cancel link', function () {
    $user = deletionUser();
    $this->actingAs($user);

    Volt::test('profile.delete-account')
        ->set('password', 'secret-password')
        ->call('scheduleDeletion')
        ->assertHasNoErrors()
        ->assertSee('will be permanently deleted');

    expect($user->fresh()->deletion_scheduled_at->toDateString())->toBe(now()->addDays(7)->toDateString());
    Notification::assertSentTo($user, AccountDeletionScheduledNotification::class);
});

it('rejects a wrong password and throttles repeated attempts', function () {
    $user = deletionUser();
    $this->actingAs($user);

    $component = Volt::test('profile.delete-account');

    foreach (range(1, 5) as $attempt) {
        $component->set('password', 'wrong')->call('scheduleDeletion')->assertHasErrors(['password']);
    }

    $component->set('password', 'secret-password')->call('scheduleDeletion')->assertHasErrors(['password']);

    expect($user->fresh()->deletion_scheduled_at)->toBeNull();
});

it('requires a valid 2FA code when 2FA is enabled', function () {
    $user = deletionUser();
    $secret = enableTwoFactor($user);
    $this->actingAs($user);

    Volt::test('profile.delete-account')
        ->set('password', 'secret-password')
        ->call('scheduleDeletion')
        ->assertHasErrors(['twoFactorCode'])
        ->set('twoFactorCode', '000000')
        ->call('scheduleDeletion')
        ->assertHasErrors(['twoFactorCode'])
        ->set('twoFactorCode', app(Google2FA::class)->getCurrentOtp($secret))
        ->call('scheduleDeletion')
        ->assertHasNoErrors();

    expect($user->fresh()->deletion_scheduled_at)->not->toBeNull();
});

it('accepts and consumes a recovery code', function () {
    $user = deletionUser();
    enableTwoFactor($user);
    $this->actingAs($user);

    Volt::test('profile.delete-account')
        ->set('password', 'secret-password')
        ->set('twoFactorCode', 'recovery-one')
        ->call('scheduleDeletion')
        ->assertHasNoErrors();

    expect($user->fresh()->deletion_scheduled_at)->not->toBeNull();
    expect($user->fresh()->recoveryCodes())->not->toContain('recovery-one');
});

it('blocks deletion while an owned workspace has other members or a paid plan', function () {
    $user = deletionUser();
    $workspace = $user->workspaces()->firstOrFail();
    $workspace->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);
    $workspace->forceFill(['plan' => 'pro'])->save();
    $this->actingAs($user);

    Volt::test('profile.delete-account')
        ->assertSee('Remove the other members')
        ->assertSee('Cancel the paid plan')
        ->set('password', 'secret-password')
        ->call('scheduleDeletion');

    expect($user->fresh()->deletion_scheduled_at)->toBeNull();
});

it('refuses to schedule deletion when the flag is off', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    $this->actingAs($user);

    Volt::test('profile.delete-account')
        ->set('password', 'secret-password')
        ->call('scheduleDeletion')
        ->assertForbidden();
});

it('cancels within the grace period from the profile or the signed email link', function () {
    $user = deletionUser();
    AccountDeletion::schedule($user);
    $this->actingAs($user);

    Volt::test('profile.delete-account')->call('cancelDeletion');
    expect($user->fresh()->deletion_scheduled_at)->toBeNull();

    AccountDeletion::schedule($user);
    $cancelUrl = (new AccountDeletionScheduledNotification)->cancelUrl($user->fresh());

    auth()->logout();
    $this->get(strtok($cancelUrl, '?'))->assertForbidden();
    $this->get($cancelUrl)->assertRedirect(route('login'));

    expect($user->fresh()->deletion_scheduled_at)->toBeNull();
});

it('does not purge before the grace period ends', function () {
    $user = deletionUser();
    AccountDeletion::schedule($user);

    $this->travel(6)->days();
    $this->artisan('app:purge-deleted-accounts')->assertSuccessful();

    expect(User::find($user->id))->not->toBeNull();
});

it('removes every row and file belonging to the user after the grace period', function () {
    Storage::fake(config('filesystems.default'));
    $disk = Storage::disk(config('filesystems.default'));

    $user = deletionUser(['avatar_path' => 'avatars/me.png']);
    $disk->put('avatars/me.png', 'avatar');

    // Owned content, including a file uploaded by the user.
    $ownedBoard = Board::factory()->create(['user_id' => $user->id]);
    $ownedCard = Card::factory()->create(['column_id' => Column::factory()->create(['board_id' => $ownedBoard->id])->id]);
    $disk->put('attachments/owned.png', 'img');
    Attachment::factory()->create([
        'attachable_type' => Card::class, 'attachable_id' => $ownedCard->id, 'user_id' => $user->id,
        'type' => Attachment::TYPE_IMAGE, 'path' => 'attachments/owned.png',
    ]);
    ActivityLogger::log('card.created', $ownedCard, ['title' => 'secret'], $user->id);

    // A teammate's workspace where the user created a board and commented with an attachment.
    $teammate = User::factory()->create();
    $teamWorkspace = $teammate->workspaces()->firstOrFail();
    $teamWorkspace->users()->attach($user->id, ['role' => Workspace::ROLE_MEMBER]);
    $boardCreatedByUser = Board::factory()->create(['user_id' => $user->id, 'workspace_id' => $teamWorkspace->id]);
    $teamCard = Card::factory()->create(['column_id' => Column::factory()->create(['board_id' => $boardCreatedByUser->id])->id]);
    $comment = Comment::factory()->create(['card_id' => $teamCard->id, 'user_id' => $user->id]);
    $disk->put('attachments/comment.pdf', 'pdf');
    Attachment::factory()->create([
        'attachable_type' => Comment::class, 'attachable_id' => $comment->id, 'user_id' => $teammate->id,
        'type' => Attachment::TYPE_DOCUMENT, 'path' => 'attachments/comment.pdf',
    ]);

    // Account-level rows.
    $user->createToken('bot', ['bot:read']);
    ActivityLogger::log('login.failed', null, ['email' => $user->email], $user->id);
    DB::table('sessions')->insert(['id' => 'session-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);

    AccountDeletion::schedule($user);
    $this->travelTo(now()->addDays(7)->addMinute());
    $this->artisan('app:purge-deleted-accounts')->assertSuccessful();

    expect(User::find($user->id))->toBeNull();
    expect(Workspace::where('owner_id', $user->id)->exists())->toBeFalse();
    expect(Board::find($ownedBoard->id))->toBeNull();
    expect(Comment::find($comment->id))->toBeNull();
    expect(Attachment::count())->toBe(0);
    expect(DB::table('personal_access_tokens')->count())->toBe(0);
    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
    expect(DB::table('features')->where('scope', 'like', '%|'.$user->id)->exists())->toBeFalse();
    expect($disk->allFiles())->toBe([]);

    // The board the user made in the teammate's workspace stays, handed to the owner.
    expect(Board::find($boardCreatedByUser->id)->user_id)->toBe($teammate->id);

    // Activity about deleted content is gone; the rest is anonymised.
    expect(ActivityLog::where('user_id', $user->id)->exists())->toBeFalse();
    expect(ActivityLog::where('subject_type', Card::class)->where('subject_id', $ownedCard->id)->exists())->toBeFalse();
    expect(json_encode(ActivityLog::all()))->not->toContain($user->email);
});

it('skips the purge if members joined an owned workspace during the grace period', function () {
    $user = deletionUser();
    AccountDeletion::schedule($user);
    $user->workspaces()->firstOrFail()->users()->attach(User::factory()->create()->id, ['role' => Workspace::ROLE_MEMBER]);

    $this->travel(8)->days();
    $this->artisan('app:purge-deleted-accounts')->assertSuccessful();

    expect(User::find($user->id))->not->toBeNull();
});
