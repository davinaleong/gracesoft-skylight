<?php

namespace App\Jobs;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendSlackMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Workspace $workspace,
        public string $text,
    ) {}

    public function handle(): void
    {
        if (blank($this->workspace->slack_webhook_url)) {
            return;
        }

        Http::timeout(10)->post($this->workspace->slack_webhook_url, [
            'text' => $this->text,
        ]);
    }
}
