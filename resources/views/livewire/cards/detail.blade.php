<?php

use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Card $card;

    // Dates
    public string $startsAt = '';
    public string $endsAt = '';

    // Checklist creation
    public string $newChecklistName = '';

    // Checklist item creation
    public ?int $addingItemToChecklist = null;
    public string $newItemBody = '';

    // Comments
    public string $newCommentBody = '';

    public function mount(Card $card): void
    {
        $this->card = $card;
        $this->startsAt = $card->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $card->ends_at?->format('Y-m-d') ?? '';
    }

    #[Computed]
    public function checklists()
    {
        return $this->card->checklists()->with('items')->get();
    }

    public function saveDates(): void
    {
        $this->validate([
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
        ]);

        $this->card->update([
            'starts_at' => $this->startsAt ?: null,
            'ends_at' => $this->endsAt ?: null,
        ]);
    }

    public function createChecklist(): void
    {
        $this->validate(['newChecklistName' => ['required', 'string', 'max:255']]);

        $this->card->checklists()->create(['name' => $this->newChecklistName]);
        $this->reset('newChecklistName');
    }

    public function deleteChecklist(int $checklistId): void
    {
        $this->card->checklists()->findOrFail($checklistId)->delete();
    }

    public function createItem(int $checklistId): void
    {
        $this->validate(['newItemBody' => ['required', 'string', 'max:255']]);

        $checklist = $this->card->checklists()->findOrFail($checklistId);
        $checklist->items()->create([
            'body' => $this->newItemBody,
            'position' => $checklist->items()->count(),
        ]);

        $this->reset('newItemBody', 'addingItemToChecklist');
    }

    public function toggleItem(int $itemId): void
    {
        $item = ChecklistItem::whereHas('checklist', fn ($q) => $q->where('card_id', $this->card->id))
            ->findOrFail($itemId);

        $item->update(['is_completed' => ! $item->is_completed]);
    }

    public function deleteItem(int $itemId): void
    {
        ChecklistItem::whereHas('checklist', fn ($q) => $q->where('card_id', $this->card->id))
            ->findOrFail($itemId)
            ->delete();
    }

    #[Computed]
    public function comments()
    {
        return $this->card->comments()->with('user')->get();
    }

    public function addComment(): void
    {
        $this->validate(['newCommentBody' => ['required', 'string', 'max:2000']]);

        $this->card->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newCommentBody,
        ]);

        $this->reset('newCommentBody');
    }

    public function deleteComment(int $commentId): void
    {
        $this->card->comments()
            ->where('user_id', auth()->id())
            ->findOrFail($commentId)
            ->delete();
    }
};
?>

<div class="space-y-6">
    {{-- Card title --}}
    <div>
        <h2 class="text-lg font-semibold">{{ $card->title }}</h2>
        @if ($card->description)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $card->description }}</p>
        @endif
    </div>

    {{-- Dates --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 p-4 ring-1 ring-gray-200 dark:ring-gray-800">
        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Dates</h3>
        <form wire:submit="saveDates" class="grid grid-cols-2 gap-4">
            <div>
                <label for="startsAt" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Start date</label>
                <input id="startsAt" type="date" wire:model="startsAt"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('startsAt') border-red-500 @enderror">
                @error('startsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="endsAt" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Due date</label>
                <input id="endsAt" type="date" wire:model="endsAt"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('endsAt') border-red-500 @enderror">
                @error('endsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white shadow-xs transition-colors">
                    Save dates
                </button>
                @if ($card->starts_at || $card->ends_at)
                    <span class="ml-3 text-xs text-gray-500">
                        @if ($card->starts_at) Start: {{ $card->starts_at->format('d M Y') }} @endif
                        @if ($card->ends_at) &middot; Due: <span @class(['text-red-600 dark:text-red-400 font-medium' => $card->ends_at->isPast()])>{{ $card->ends_at->format('d M Y') }}</span> @endif
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- Checklists --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Checklists</h3>
        </div>

        @foreach ($this->checklists as $checklist)
            @php
                $total = $checklist->items->count();
                $done = $checklist->items->where('is_completed', true)->count();
                $pct = $total > 0 ? round($done / $total * 100) : 0;
            @endphp
            <div class="rounded-xl bg-white dark:bg-gray-900 p-4 ring-1 ring-gray-200 dark:ring-gray-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ $checklist->name }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $done }}/{{ $total }}</span>
                        <button wire:click="deleteChecklist({{ $checklist->id }})" wire:confirm="Delete this checklist?"
                            class="text-gray-400 hover:text-red-600 transition-colors" aria-label="Delete checklist">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                {{-- Progress bar --}}
                @if ($total > 0)
                    <div class="mb-3 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-1.5 rounded-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                @endif

                {{-- Items --}}
                <div class="space-y-1.5 mb-3">
                    @foreach ($checklist->items as $item)
                        <div class="flex items-start gap-2.5">
                            <input
                                type="checkbox"
                                wire:click="toggleItem({{ $item->id }})"
                                @checked($item->is_completed)
                                class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span @class(['text-sm flex-1', 'line-through text-gray-400' => $item->is_completed])>{{ $item->body }}</span>
                            <button wire:click="deleteItem({{ $item->id }})" class="shrink-0 text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Add item --}}
                @if ($addingItemToChecklist === $checklist->id)
                    <form wire:submit="createItem({{ $checklist->id }})" class="flex gap-2 mt-2">
                        <input type="text" wire:model="newItemBody" autofocus placeholder="Item text&hellip;"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white">Add</button>
                        <button type="button" wire:click="$set('addingItemToChecklist', null)" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs">Cancel</button>
                    </form>
                @else
                    <button wire:click="$set('addingItemToChecklist', {{ $checklist->id }})"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        + Add item
                    </button>
                @endif
            </div>
        @endforeach

        {{-- Create new checklist --}}
        <form wire:submit="createChecklist" class="flex gap-2">
            <input type="text" wire:model="newChecklistName" placeholder="Add a checklist&hellip;"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('newChecklistName') border-red-500 @enderror">
            <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors">
                Add
            </button>
        </form>
        @error('newChecklistName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Comments --}}
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Comments</h3>

        {{-- Add comment --}}
        <form wire:submit="addComment" class="space-y-2">
            <textarea
                wire:model="newCommentBody"
                rows="3"
                placeholder="Write a comment&hellip;"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none @error('newCommentBody') border-red-500 @enderror"
            ></textarea>
            @error('newCommentBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors">
                Post comment
            </button>
        </form>

        {{-- Comments list --}}
        @if ($this->comments->isNotEmpty())
            <div class="space-y-3">
                @foreach ($this->comments as $comment)
                    <div class="rounded-xl bg-white dark:bg-gray-900 p-3.5 ring-1 ring-gray-200 dark:ring-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $comment->user->name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                @if ($comment->user_id === auth()->id())
                                    <button
                                        wire:click="deleteComment({{ $comment->id }})"
                                        wire:confirm="Delete this comment?"
                                        class="text-gray-300 hover:text-red-500 transition-colors"
                                        aria-label="Delete comment"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $comment->body }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">No comments yet.</p>
        @endif
    </div>
</div>

</div>
