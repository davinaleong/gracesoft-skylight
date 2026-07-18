<?php

use App\Models\Webhook;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public Workspace $workspace;

    public string $slackWebhookUrl = '';

    /** @var array<int, string> */
    public array $slackEvents = [];

    public ?string $testResult = null;

    public function mount(): void
    {
        $this->workspace = auth()->user()->currentWorkspace();

        abort_unless($this->workspace->canManageMembers(auth()->user()), 403);

        $this->slackWebhookUrl = $this->workspace->slack_webhook_url ?? '';
        $this->slackEvents = $this->workspace->slack_events ?? array_keys(Webhook::EVENTS);
    }

    public function connectSlack(): void
    {
        $data = $this->validate([
            'slackWebhookUrl' => ['required', 'url', 'starts_with:https://hooks.slack.com/'],
            'slackEvents' => ['required', 'array', 'min:1'],
            'slackEvents.*' => [Rule::in(array_keys(Webhook::EVENTS))],
        ]);

        $this->workspace->update([
            'slack_webhook_url' => $data['slackWebhookUrl'],
            'slack_events' => $data['slackEvents'],
        ]);

        $this->testResult = null;
    }

    public function disconnectSlack(): void
    {
        $this->workspace->update(['slack_webhook_url' => null, 'slack_events' => null]);
        $this->reset('slackWebhookUrl', 'slackEvents', 'testResult');
    }

    public function sendTestMessage(): void
    {
        abort_unless($this->workspace->slackIsConnected(), 404);

        try {
            $response = Http::timeout(10)->post($this->workspace->slack_webhook_url, [
                'text' => ':wave: This is a test message from '.config('app.name', 'Skylight').'.',
            ]);

            $this->testResult = $response->successful()
                ? 'Test message sent — check your Slack channel.'
                : 'Slack responded with an error (HTTP '.$response->status().'). Double-check the webhook URL.';
        } catch (\Throwable) {
            $this->testResult = 'Could not reach Slack — double-check the webhook URL.';
        }
    }
};
?>

<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Integrations</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Connect <strong>{{ $workspace->name }}</strong> to other tools.</p>
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-base font-semibold">Slack</h3>
            @if ($workspace->slackIsConnected())
                <span class="text-xs font-medium text-green-600 dark:text-green-400">Connected</span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Post card updates to a Slack channel using an
            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1 py-0.5">Incoming Webhook</span>
            URL from your Slack workspace's app settings.
        </p>

        <form wire:submit="connectSlack" class="space-y-4">
            <div>
                <label for="slackWebhookUrl" class="block text-sm font-medium mb-1.5">Slack webhook URL</label>
                <input
                    id="slackWebhookUrl"
                    type="url"
                    wire:model="slackWebhookUrl"
                    placeholder="https://hooks.slack.com/services/…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('slackWebhookUrl') border-red-500 @enderror"
                >
                @error('slackWebhookUrl')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="block text-sm font-medium mb-1.5">Notify on</span>
                <div class="space-y-2">
                    @foreach (Webhook::EVENTS as $key => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="slackEvents" value="{{ $key }}" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5">{{ $key }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('slackEvents')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="connectSlack"
                    class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="connectSlack">{{ $workspace->slackIsConnected() ? 'Save changes' : 'Connect Slack' }}</span>
                    <span wire:loading wire:target="connectSlack">Saving…</span>
                </button>

                @if ($workspace->slackIsConnected())
                    <button
                        type="button"
                        wire:click="sendTestMessage"
                        wire:loading.attr="disabled"
                        wire:target="sendTestMessage"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 px-3.5 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="sendTestMessage">Send test message</span>
                        <span wire:loading wire:target="sendTestMessage">Sending…</span>
                    </button>

                    <button
                        type="button"
                        wire:click="disconnectSlack"
                        wire:confirm="Disconnect Slack? Card updates will stop posting to your channel."
                        class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-500"
                    >
                        Disconnect
                    </button>
                @endif
            </div>
        </form>

        @if ($testResult)
            <p class="mt-3 text-sm {{ str_starts_with($testResult, 'Test message sent') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $testResult }}
            </p>
        @endif
    </div>
</div>
