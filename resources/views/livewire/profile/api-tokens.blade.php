<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $tokenName = '';

    public ?string $plainTextToken = null;

    public bool $showCreateForm = false;

    #[Computed]
    public function tokens()
    {
        return auth()->user()->tokens()->latest()->get();
    }

    public function create(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:100'],
        ]);

        $token = auth()->user()->createToken($this->tokenName);

        $this->plainTextToken = $token->plainTextToken;
        $this->reset('tokenName', 'showCreateForm');
        unset($this->tokens);
    }

    public function revoke(int $tokenId): void
    {
        auth()->user()->tokens()->whereKey($tokenId)->delete();

        unset($this->tokens);
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
    }
};
?>

<div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold">API tokens</h3>
        <button
            wire:click="$set('showCreateForm', true)"
            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
        >
            + New token
        </button>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Use API tokens to authenticate requests to the public REST API from scripts, Zapier, or your own integrations. Send them as <code class="text-xs bg-gray-100 dark:bg-gray-800 rounded px-1 py-0.5">Authorization: Bearer &lt;token&gt;</code>.
    </p>

    @if ($plainTextToken)
        <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3.5 ring-1 ring-amber-200 dark:ring-amber-900/40">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1.5">Copy this token now — you won't be able to see it again.</p>
            <code class="block text-xs bg-white dark:bg-gray-950 rounded-lg px-3 py-2 break-all ring-1 ring-amber-200 dark:ring-amber-900/40">{{ $plainTextToken }}</code>
            <button wire:click="dismissToken" class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400 hover:underline">Done, I've copied it</button>
        </div>
    @endif

    @if ($showCreateForm)
        <form wire:submit="create" class="mb-4 flex items-end gap-3">
            <div class="flex-1">
                <label for="tokenName" class="block text-sm font-medium mb-1.5">Token name</label>
                <input
                    id="tokenName"
                    type="text"
                    wire:model="tokenName"
                    placeholder="e.g. Zapier, CLI script"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('tokenName') border-red-500 @enderror"
                >
                @error('tokenName')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="create"
                class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="create">Create</span>
                <span wire:loading wire:target="create">Creating…</span>
            </button>
        </form>
    @endif

    @if ($this->tokens->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500">No API tokens yet.</p>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($this->tokens as $token)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-medium">{{ $token->name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Created {{ $token->created_at->diffForHumans() }}
                            @if ($token->last_used_at)
                                &middot; last used {{ $token->last_used_at->diffForHumans() }}
                            @else
                                &middot; never used
                            @endif
                        </p>
                    </div>
                    <button
                        wire:click="revoke({{ $token->id }})"
                        wire:confirm="Revoke this token? Anything using it will stop working immediately."
                        class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-500"
                    >
                        Revoke
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
