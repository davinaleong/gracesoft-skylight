<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'workspace_id', 'name', 'color'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * Default a tag to its creator's personal workspace when the caller
     * didn't set one explicitly (mirrors Board's workspace auto-fill).
     */
    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (blank($tag->workspace_id) && $tag->user_id) {
                $tag->workspace_id = User::find($tag->user_id)?->workspaces()->value('workspaces.id');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'board_tags');
    }

    public function columns(): BelongsToMany
    {
        return $this->belongsToMany(Column::class, 'column_tags');
    }
}
