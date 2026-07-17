<?php

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'created_by', 'url', 'secret', 'events', 'is_active'])]
#[Hidden(['secret'])]
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public const EVENTS = [
        'card.created' => 'Card created',
        'card.moved' => 'Card moved to another column',
        'card.completed' => 'Card moved into the board\'s last column',
        'card.deleted' => 'Card deleted',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->latest();
    }

    public static function generateSecret(): string
    {
        return Str::random(40);
    }

    public function subscribesTo(string $event): bool
    {
        return $this->is_active && in_array($event, $this->events ?? [], true);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'secret' => 'encrypted',
        ];
    }
}
