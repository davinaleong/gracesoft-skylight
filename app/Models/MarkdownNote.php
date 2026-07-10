<?php

namespace App\Models;

use Database\Factories\MarkdownNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['card_id', 'user_id', 'name', 'content'])]
class MarkdownNote extends Model
{
    /** @use HasFactory<MarkdownNoteFactory> */
    use HasFactory;

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
