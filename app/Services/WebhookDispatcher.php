<?php

namespace App\Services;

use App\Jobs\SendWebhookRequest;
use App\Models\Workspace;

class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function dispatch(Workspace $workspace, string $event, array $payload): void
    {
        $workspace->webhooks()
            ->get()
            ->filter(fn ($webhook) => $webhook->subscribesTo($event))
            ->each(function ($webhook) use ($event, $payload) {
                SendWebhookRequest::dispatch($webhook, $event, [
                    'event' => $event,
                    'data' => $payload,
                    'sent_at' => now()->toIso8601String(),
                ]);
            });
    }
}
