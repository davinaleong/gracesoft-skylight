<?php

namespace App\Concerns;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;

/**
 * Shared by Livewire/Volt components that mutate board/column/card content.
 * Viewers may read everything but never create, update, delete, move, or
 * comment on anything -- see Workspace::canEditContent().
 */
trait AuthorizesWorkspaceEditing
{
    protected function authorizeEdit(Board|Column|Card $model): void
    {
        $workspace = match (true) {
            $model instanceof Board => $model->workspace,
            $model instanceof Column => $model->board->workspace,
            $model instanceof Card => $model->column->board->workspace,
        };

        abort_unless($workspace->canEditContent(auth()->user()), 403);
    }
}
