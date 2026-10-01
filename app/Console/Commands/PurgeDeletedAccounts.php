<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountDeletion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:purge-deleted-accounts')]
#[Description('Permanently delete accounts whose deletion grace period has ended')]
class PurgeDeletedAccounts extends Command
{
    public function handle(): int
    {
        $purged = 0;

        AccountDeletion::due()->each(function (User $user) use (&$purged) {
            // Members may have joined an owned workspace during the grace
            // period; never delete a workspace other people still use.
            if (AccountDeletion::blockers($user) !== []) {
                Log::warning('Skipped account purge: deletion is blocked.', ['user_id' => $user->id]);

                return;
            }

            AccountDeletion::purge($user);
            $purged++;
        });

        $this->info("Purged {$purged} account(s).");

        return self::SUCCESS;
    }
}
