<?php

namespace App\Models;

use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['webhook_id', 'event', 'payload', 'response_status', 'successful', 'error'])]
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
