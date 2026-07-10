<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['column_id', 'title', 'description', 'starts_at', 'ends_at', 'position'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
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
