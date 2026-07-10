<?php

namespace App\Observers;

use App\Models\Card;
use App\Services\ActivityLogger;

class CardObserver
{
    public function created(Card $card): void
    {
        ActivityLogger::log('card.created', $card);
    }

    public function updated(Card $card): void
    {
        $dirty = $card->getDirty();
        $ignore = ['position', 'column_id', 'updated_at'];
        $moved = isset($dirty['column_id']);
        $relevant = array_diff_key($dirty, array_flip($ignore));

        if ($moved) {
            ActivityLogger::log('card.moved', $card, [
                'from_column_id' => $card->getOriginal('column_id'),
                'to_column_id' => $dirty['column_id'],
            ]);
        }

        if (! empty($relevant)) {
            ActivityLogger::log('card.updated', $card, ActivityLogger::diff($relevant, $card->getOriginal()));
        }
    }

    public function deleted(Card $card): void
    {
        ActivityLogger::log('card.deleted', $card, ['title' => $card->title]);
    }
}
