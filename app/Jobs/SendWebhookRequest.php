<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $body = json_encode($this->payload);

        $response = null;
        $error = null;

        try {
            $response = Http::withHeaders([
                'X-Skylight-Event' => $this->event,
                'X-Skylight-Signature' => 'sha256='.$this->webhook->sign($body),
                'Content-Type' => 'application/json',
            ])->withBody($body, 'application/json')->timeout(10)->post($this->webhook->url);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        WebhookDelivery::create([
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'response_status' => $response?->status(),
            'successful' => $response?->successful() ?? false,
            'error' => $error,
        ]);

        $this->webhook->update(['last_triggered_at' => now()]);
    }
}
