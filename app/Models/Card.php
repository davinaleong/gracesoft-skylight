<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['column_id', 'title', 'description', 'starts_at', 'ends_at', 'position', 'color'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /** @var array<string, array{light: string, dark: string}> */
    public const COLORS = [
        'yellow' => ['light' => '#FEF48B', 'dark' => '#8A7B00'],
        'pink' => ['light' => '#FF87A1', 'dark' => '#B3264B'],
        'blue' => ['light' => '#7FDBFF', 'dark' => '#0B6E8C'],
        'green' => ['light' => '#B0E57C', 'dark' => '#4B7A2C'],
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    /**
     * Cards ending today, scoped to the given user's own boards.
     */
    public function scopeDueToday(Builder $query, User $user): Builder
    {
        return $query
            ->whereHas('column.board', fn (Builder $q) => $q->where('user_id', $user->id))
            ->whereDate('ends_at', now()->toDateString());
    }

    /**
     * Cards past their end date that haven't been resolved, scoped to the
     * given user's own boards.
     */
    public function scopeOverdue(Builder $query, User $user): Builder
    {
        return $query
            ->whereHas('column.board', fn (Builder $q) => $q->where('user_id', $user->id))
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<', now()->toDateString());
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'card_labels');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function markdownNotes(): HasMany
    {
        return $this->hasMany(MarkdownNote::class)->latest();
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'position' => 'integer',
        ];
    }
}
