<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\User;
use App\Notifications\Card\CardDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-card-due-reminders')]
#[Description('Send due-today and overdue card reminder emails to all users (P2 notifications)')]
class SendCardDueReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        User::chunk(100, function (iterable $users) use (&$sent) {
            foreach ($users as $user) {
                // Due today
                if ($user->wantsNotification('due_today')) {
                    $dueToday = Card::dueToday($user)->with('column.board')->get();

                    if ($dueToday->isNotEmpty()) {
                        $user->notify(new CardDueNotification($dueToday, CardDueNotification::TYPE_DUE_TODAY));
                        $sent++;
                    }
                }

                // Overdue
                if ($user->wantsNotification('overdue')) {
                    $overdue = Card::overdue($user)->with('column.board')->get();

                    if ($overdue->isNotEmpty()) {
                        $user->notify(new CardDueNotification($overdue, CardDueNotification::TYPE_OVERDUE));
                        $sent++;
                    }
                }
            }
        });

        $this->info("Sent {$sent} card due reminder notification(s).");

        return self::SUCCESS;
    }
}
