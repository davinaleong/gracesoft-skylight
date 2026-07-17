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
        abort_unless($this->workspaceFor($model)->canEditContent(auth()->user()), 403);
    }

    /**
     * Read access: any workspace member (including viewers) may view.
     */
    protected function authorizeView(Board|Column|Card $model): void
    {
        abort_unless($this->workspaceFor($model)->hasMember(auth()->user()), 403);
    }

    private function workspaceFor(Board|Column|Card $model)
    {
        return match (true) {
            $model instanceof Board => $model->workspace,
            $model instanceof Column => $model->board->workspace,
            $model instanceof Card => $model->column->board->workspace,
        };
    }
}
