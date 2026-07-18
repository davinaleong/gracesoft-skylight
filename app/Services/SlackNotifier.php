<?php

namespace App\Services;

use App\Jobs\SendSlackMessage;
use App\Models\Card;
use App\Models\Column;
use App\Models\Workspace;

class SlackNotifier
{
    public static function notifyCardEvent(Workspace $workspace, string $event, Card $card, ?Column $fromColumn = null): void
    {
        if (! $workspace->slackNotifiesOn($event)) {
            return;
        }

        SendSlackMessage::dispatch($workspace, self::message($event, $card, $fromColumn));
    }

    private static function message(string $event, Card $card, ?Column $fromColumn): string
    {
        $board = $card->column->board;
        $title = $card->title;

        return match ($event) {
            'card.created' => ":new: *{$title}* was created in *{$card->column->name}* on *{$board->name}*",
            'card.moved' => ":twisted_rightwards_arrows: *{$title}* moved".
                ($fromColumn ? " from *{$fromColumn->name}*" : '').
                " to *{$card->column->name}* on *{$board->name}*",
            'card.completed' => ":white_check_mark: *{$title}* was completed on *{$board->name}*",
            'card.deleted' => ":wastebasket: *{$title}* was deleted from *{$board->name}*",
            default => "*{$title}* — {$event} on *{$board->name}*",
        };
    }
}
