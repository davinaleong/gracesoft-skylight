<?php

namespace App\Observers;

use App\Models\Board;
use App\Services\ActivityLogger;

class BoardObserver
{
    public function created(Board $board): void
    {
        ActivityLogger::log('board.created', $board);
    }

    public function updated(Board $board): void
    {
        $dirty = $board->getDirty();
        $ignore = ['position', 'updated_at'];
        $relevant = array_diff_key($dirty, array_flip($ignore));

        if (empty($relevant)) {
            return;
        }

        ActivityLogger::log('board.updated', $board, ActivityLogger::diff($relevant, $board->getOriginal()));
    }

    public function deleted(Board $board): void
    {
        ActivityLogger::log('board.deleted', $board, ['name' => $board->name]);
    }
}
