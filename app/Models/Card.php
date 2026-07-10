<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['column_id', 'title', 'description', 'starts_at', 'ends_at', 'position'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
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

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->latest();
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
