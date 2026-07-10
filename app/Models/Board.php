<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'name', 'description', 'position'])]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    /**
     * Auto-generate a UUID whenever a board is first created.
     * The integer PK (id) remains for SQL joins and foreign keys.
     */
    protected static function booted(): void
    {
        static::creating(function (Board $board) {
            $board->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Use the UUID as the route binding key so board IDs never appear in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'board_tags');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(BoardShareLink::class);
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
