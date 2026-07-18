<?php

namespace App\Observers;

use App\Models\Card;
use App\Models\Column;
use App\Services\ActivityLogger;
use App\Services\SlackNotifier;
use App\Services\WebhookDispatcher;

class CardObserver
{
    public function created(Card $card): void
    {
        ActivityLogger::log('card.created', $card, ['card_title' => $card->title, 'board_id' => $card->column->board_id]);

        $workspace = $card->column->board->workspace;
        WebhookDispatcher::dispatch($workspace, 'card.created', $this->payload($card));
        SlackNotifier::notifyCardEvent($workspace, 'card.created', $card);
    }

    public function updated(Card $card): void
    {
        $dirty = $card->getDirty();
        $ignore = ['position', 'column_id', 'updated_at'];
        $moved = isset($dirty['column_id']);
        $relevant = array_diff_key($dirty, array_flip($ignore));

        // If this model instance already had its `column` relation cached from
        // an earlier access in this same request/script (e.g. this Card was
        // just created moments ago in the same call), that cache would still
        // reflect the *old* column_id -- update() changes the attribute but
        // doesn't invalidate cached relations. Force a fresh lookup so a move
        // is never reported against the column the card started in.
        $card->unsetRelation('column');
        $board = $card->column->board;
        $boardId = $board->id;

        if ($moved) {
            ActivityLogger::log('card.moved', $card, [
                'card_title' => $card->title,
                'from_column_id' => $card->getOriginal('column_id'),
                'to_column_id' => $dirty['column_id'],
                'board_id' => $boardId,
            ]);

            WebhookDispatcher::dispatch($board->workspace, 'card.moved', $this->payload($card));

            $fromColumn = Column::find($card->getOriginal('column_id'));
            SlackNotifier::notifyCardEvent($board->workspace, 'card.moved', $card, $fromColumn);

            // "Completed" isn't a native concept in this app -- treat the board's
            // right-most (highest position) column as the finish line, so this
            // works for any workflow (Done, Published, Complete, ...) without
            // hardcoding column names.
            $lastColumnId = $board->columns()->reorder('position', 'desc')->value('id');

            if ($lastColumnId !== null && (int) $dirty['column_id'] === $lastColumnId) {
                WebhookDispatcher::dispatch($board->workspace, 'card.completed', $this->payload($card));
                SlackNotifier::notifyCardEvent($board->workspace, 'card.completed', $card);
            }
        }

        if (! empty($relevant)) {
            ActivityLogger::log('card.updated', $card, [
                ...ActivityLogger::diff($relevant, $card->getOriginal()),
                'card_title' => $card->title,
                'board_id' => $boardId,
            ]);
        }
    }

    public function deleted(Card $card): void
    {
        ActivityLogger::log('card.deleted', $card, ['card_title' => $card->title, 'board_id' => $card->column->board_id]);

        $workspace = $card->column->board->workspace;
        WebhookDispatcher::dispatch($workspace, 'card.deleted', $this->payload($card));
        SlackNotifier::notifyCardEvent($workspace, 'card.deleted', $card);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Card $card): array
    {
        return [
            'card_id' => $card->id,
            'title' => $card->title,
            'column_id' => $card->column_id,
            'board_id' => $card->column->board->uuid,
        ];
    }
}
