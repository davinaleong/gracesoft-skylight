<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Notifications\Auth\NewIpLoginNotification;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Notifications\Auth\RecoveryCodeUsedNotification;
use App\Notifications\Auth\SuspiciousLoginNotification;
use App\Notifications\Auth\WelcomeNotification;
use App\Notifications\Board\ShareLinkCreatedNotification;
use App\Notifications\Board\ShareLinkRevokedNotification;
use App\Notifications\Card\CardDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

#[Signature('app:test-mail {--type= : Notification type to send} {--to= : Recipient email (defaults to first user)}')]
#[Description('Send a test notification email to preview the mail template design')]
class TestMail extends Command
{
    /** @var array<string, string> */
    private const TYPES = [
        'welcome' => 'Welcome (P1 #6)',
        'password-changed' => 'Password changed confirmation (P0 #2)',
        'new-ip-login' => 'Login from new IP (P0 #5)',
        'suspicious-login' => 'Suspicious login alert (P0 #4)',
        'recovery-code-used' => '2FA recovery code used (P0 #3)',
        'share-link-created' => 'Share link created (P1 #8)',
        'share-link-revoked' => 'Share link revoked (P1 #9)',
        'card-due-today' => 'Card due today (P2 #10)',
        'card-overdue' => 'Card overdue (P2 #11)',
    ];

    public function handle(): int
    {
        $type = $this->option('type') ?? $this->choice(
            'Which notification would you like to preview?',
            array_values(self::TYPES),
        );

        // Resolve type key from display label if picked from choice menu
        $typeKey = array_search($type, self::TYPES) ?: $type;

        if (! array_key_exists($typeKey, self::TYPES)) {
            $this->error("Unknown type '{$typeKey}'. Valid types: ".implode(', ', array_keys(self::TYPES)));

            return self::FAILURE;
        }

        $recipientEmail = $this->option('to');
        $user = $recipientEmail
            ? User::where('email', $recipientEmail)->firstOrFail()
            : User::first();

        if (! $user) {
            $this->error('No user found. Create one first: php artisan app:create-user');

            return self::FAILURE;
        }

        $notification = $this->buildNotification($typeKey, $user);

        $user->notify($notification);

        $this->info("✓ Sent '{$typeKey}' notification to {$user->email}");

        return self::SUCCESS;
    }

    private function buildNotification(string $type, User $user): Notification
    {
        return match ($type) {
            'welcome' => new WelcomeNotification,
            'password-changed' => new PasswordChangedNotification,
            'new-ip-login' => new NewIpLoginNotification,
            'suspicious-login' => new SuspiciousLoginNotification(failedAttempts: 7),
            'recovery-code-used' => new RecoveryCodeUsedNotification,
            'share-link-created' => new ShareLinkCreatedNotification(
                board: Board::factory()->make(['name' => 'My Test Board']),
                rawToken: 'ExampleToken-abc123xyz789',
                canSeeComments: true,
                canSeeAttachments: false,
            ),
            'share-link-revoked' => new ShareLinkRevokedNotification(
                board: Board::factory()->make(['name' => 'My Test Board']),
            ),
            'card-due-today' => new CardDueNotification(
                cards: $this->fakeCards($user),
                type: CardDueNotification::TYPE_DUE_TODAY,
            ),
            'card-overdue' => new CardDueNotification(
                cards: $this->fakeCards($user, daysAgo: 3),
                type: CardDueNotification::TYPE_OVERDUE,
            ),
        };
    }

    private function fakeCards(User $user, int $daysAgo = 0): Collection
    {
        $board = Board::factory()->make(['name' => 'My Test Board', 'user_id' => $user->id]);
        $column = Column::factory()->make(['name' => 'In Progress', 'board_id' => 1]);
        $column->setRelation('board', $board);

        return collect([
            tap(Card::factory()->make([
                'title' => 'Fix the critical bug',
                'ends_at' => $daysAgo > 0 ? now()->subDays($daysAgo) : now(),
            ]), fn ($c) => $c->setRelation('column', $column)),
            tap(Card::factory()->make([
                'title' => 'Write release notes',
                'ends_at' => $daysAgo > 0 ? now()->subDays($daysAgo - 1) : now(),
            ]), fn ($c) => $c->setRelation('column', $column)),
        ]);
    }
}
