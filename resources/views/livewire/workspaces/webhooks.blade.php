<?php

use App\Models\Webhook;
use App\Models\Workspace;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Workspace $workspace;

    public bool $showCreateForm = false;

    public string $url = '';

    /** @var array<int, string> */
    public array $selectedEvents = [];

    public ?string $plainTextSecret = null;

    public function mount(): void
    {
        $this->workspace = auth()->user()->currentWorkspace();

        abort_unless($this->workspace->canManageMembers(auth()->user()), 403);
    }

    #[Computed]
    public function webhooks()
    {
        return $this->workspace->webhooks()->with(['deliveries' => fn ($q) => $q->limit(3)])->latest()->get();
    }

    public function create(): void
    {
        $data = $this->validate([
            'url' => ['required', 'url', 'max:2048'],
            'selectedEvents' => ['required', 'array', 'min:1'],
            'selectedEvents.*' => [Rule::in(array_keys(Webhook::EVENTS))],
        ]);

        $secret = Webhook::generateSecret();

        $this->workspace->webhooks()->create([
            'created_by' => auth()->id(),
            'url' => $data['url'],
            'secret' => $secret,
            'events' => $data['selectedEvents'],
            'is_active' => true,
        ]);

        $this->plainTextSecret = $secret;
        $this->reset('url', 'selectedEvents', 'showCreateForm');
        unset($this->webhooks);
    }

    public function toggleActive(int $webhookId): void
    {
        $webhook = $this->workspace->webhooks()->findOrFail($webhookId);
        $webhook->update(['is_active' => ! $webhook->is_active]);

        unset($this->webhooks);
    }

    public function delete(int $webhookId): void
    {
        $this->workspace->webhooks()->findOrFail($webhookId)->delete();

        unset($this->webhooks);
    }

    public function dismissSecret(): void
    {
        $this->plainTextSecret = null;
    }
};
?>

<div class="max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Webhooks</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send an HTTP POST to your own endpoint when card events happen in <strong>{{ $workspace->name }}</strong>.</p>
        </div>
        <button
            wire:click="$set('showCreateForm', true)"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            + New webhook
        </button>
    </div>

    @if ($plainTextSecret)
        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3.5 ring-1 ring-amber-200 dark:ring-amber-900/40">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1.5">Copy this signing secret now — you won't be able to see it again. Use it to verify the <code class="text-xs">X-Skylight-Signature</code> header on each request.</p>
            <code class="block text-xs bg-white dark:bg-gray-950 rounded-lg px-3 py-2 break-all ring-1 ring-amber-200 dark:ring-amber-900/40">{{ $plainTextSecret }}</code>
            <button wire:click="dismissSecret" class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400 hover:underline">Done, I've copied it</button>
        </div>
    @endif

    @if ($showCreateForm)
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="mb-4 text-base font-semibold">New webhook</h3>
            <form wire:submit="create" class="space-y-4">
                <div>
                    <label for="url" class="block text-sm font-medium mb-1.5">Endpoint URL</label>
                    <input
                        id="url"
                        type="url"
                        wire:model="url"
                        placeholder="https://example.com/webhooks/skylight"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('url') border-red-500 @enderror"
                    >
                    @error('url')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium mb-1.5">Events</span>
                    <div class="space-y-2">
                        @foreach (\App\Models\Webhook::EVENTS as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="selectedEvents" value="{{ $key }}" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5">{{ $key }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedEvents')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="create">Create webhook</span>
                        <span wire:loading wire:target="create">Creating…</span>
                    </button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($this->webhooks->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500">No webhooks configured yet.</p>
    @else
        <div class="space-y-4">
            @foreach ($this->webhooks as $webhook)
                <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $webhook->url }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach ($webhook->events as $event)
                                    <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5">{{ $event }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                @if ($webhook->is_active)
                                    <span class="text-green-600 dark:text-green-400">Active</span>
                                @else
                                    <span class="text-gray-400">Paused</span>
                                @endif
                                @if ($webhook->last_triggered_at)
                                    &middot; last triggered {{ $webhook->last_triggered_at->diffForHumans() }}
                                @else
                                    &middot; never triggered
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <button wire:click="toggleActive({{ $webhook->id }})" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                {{ $webhook->is_active ? 'Pause' : 'Resume' }}
                            </button>
                            <button
                                wire:click="delete({{ $webhook->id }})"
                                wire:confirm="Delete this webhook?"
                                class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-500"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    @if ($webhook->deliveries->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Recent deliveries</p>
                            <ul class="space-y-1">
                                @foreach ($webhook->deliveries as $delivery)
                                    <li class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        @if ($delivery->successful)
                                            <span class="text-green-600 dark:text-green-400">&#10003;</span>
                                        @else
                                            <span class="text-red-500">&#10007;</span>
                                        @endif
                                        <span class="font-mono">{{ $delivery->event }}</span>
                                        <span>{{ $delivery->response_status ?? 'no response' }}</span>
                                        <span>&middot; {{ $delivery->created_at->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
