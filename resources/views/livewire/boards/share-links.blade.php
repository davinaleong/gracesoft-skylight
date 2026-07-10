<?php

use App\Models\Board;
use App\Models\BoardShareLink;
use App\Notifications\Board\ShareLinkCreatedNotification;
use App\Notifications\Board\ShareLinkRevokedNotification;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Board $board;

    public bool $canSeeComments = false;
    public bool $canSeeAttachments = false;
    public ?string $newlyGeneratedToken = null;

    public function mount(Board $board): void
    {
        $this->board = $board;
    }

    #[Computed]
    public function shareLinks()
    {
        return $this->board->shareLinks()->latest()->get();
    }

    public function generate(): void
    {
        ['token' => $token, 'hash' => $hash] = BoardShareLink::generateToken();

        $this->board->shareLinks()->create([
            'token_hash' => $hash,
            'can_see_comments' => $this->canSeeComments,
            'can_see_attachments' => $this->canSeeAttachments,
        ]);

        ActivityLogger::log('share_link.created', $this->board);

        // P1 #8: email the owner the token (shown once, never re-read from DB)
        auth()->user()->notify(new ShareLinkCreatedNotification(
            board: $this->board,
            rawToken: $token,
            canSeeComments: $this->canSeeComments,
            canSeeAttachments: $this->canSeeAttachments,
        ));

        $this->newlyGeneratedToken = $token;
        $this->reset('canSeeComments', 'canSeeAttachments');
    }

    public function revoke(int $linkId): void
    {
        $link = $this->board->shareLinks()->findOrFail($linkId);
        $link->update(['revoked_at' => now()]);

        ActivityLogger::log('share_link.revoked', $this->board);

        // P1 #9: confirmation email on revoke
        auth()->user()->notify(new ShareLinkRevokedNotification($this->board));

        $this->newlyGeneratedToken = null;
    }
};
?>

<div class="space-y-4">
    {{-- Newly generated token (shown once) --}}
    @if ($newlyGeneratedToken)
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 ring-1 ring-green-200 dark:ring-green-800">
            <p class="mb-2 text-sm font-semibold text-green-800 dark:text-green-300">Share link created — copy it now, it won't be shown again:</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 overflow-x-auto rounded bg-white dark:bg-gray-900 px-3 py-1.5 text-sm font-mono text-gray-800 dark:text-gray-200">
                    {{ route('viewer', $newlyGeneratedToken) }}
                </code>
                <button
                    x-data
                    @click="navigator.clipboard.writeText('{{ route('viewer', $newlyGeneratedToken) }}')"
                    class="shrink-0 rounded-lg border border-green-300 dark:border-green-700 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors"
                >
                    Copy
                </button>
            </div>
        </div>
    @endif

    {{-- Existing links --}}
    @if ($this->shareLinks->isNotEmpty())
        <div class="space-y-2">
            @foreach ($this->shareLinks as $link)
                <div @class([
                    'flex items-center justify-between rounded-lg p-3 ring-1 text-sm',
                    'bg-white dark:bg-gray-900 ring-gray-200 dark:ring-gray-800' => $link->isActive(),
                    'bg-gray-50 dark:bg-gray-900/50 ring-gray-100 dark:ring-gray-800/50 opacity-60' => $link->isRevoked(),
                ])>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' => $link->isActive(),
                                'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $link->isRevoked(),
                            ])>
                                {{ $link->isActive() ? 'Active' : 'Revoked' }}
                            </span>
                            @if ($link->can_see_comments)
                                <span class="text-xs text-gray-500">+ comments</span>
                            @endif
                            @if ($link->can_see_attachments)
                                <span class="text-xs text-gray-500">+ attachments</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-gray-400">
                            Created {{ $link->created_at->diffForHumans() }}
                            @if ($link->isRevoked()) &middot; Revoked {{ $link->revoked_at->diffForHumans() }} @endif
                        </p>
                    </div>
                    @if ($link->isActive())
                        <button
                            wire:click="revoke({{ $link->id }})"
                            wire:confirm="Revoke this share link? Anyone with it will lose access."
                            class="shrink-0 rounded-lg border border-red-300 dark:border-red-800 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        >
                            Revoke
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Generate new link --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 space-y-3">
        <p class="text-sm font-medium">Generate a read-only share link</p>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="canSeeComments" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Show comments
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="canSeeAttachments" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Show attachments
            </label>
        </div>
        <button
            wire:click="generate"
            class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors"
        >
            Generate link
        </button>
    </div>
</div>

</div>
