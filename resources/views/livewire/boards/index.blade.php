<?php

use App\Models\Board;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showCreateForm = false;
    public string $name = '';
    public string $description = '';

    #[Computed]
    public function boards()
    {
        return auth()->user()->boards()->orderBy('position')->get();
    }

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        auth()->user()->boards()->create([
            'name' => $this->name,
            'description' => $this->description,
            'position' => auth()->user()->boards()->count(),
        ]);

        $this->reset('name', 'description', 'showCreateForm');
    }

    public function delete(int $boardId): void
    {
        auth()->user()->boards()->findOrFail($boardId)->delete();
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-semibold">My Boards</h1>
        <button
            wire:click="$set('showCreateForm', true)"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New board
        </button>
    </div>

    @if ($showCreateForm)
        <div class="mb-6 rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h2 class="mb-4 text-base font-semibold">Create a new board</h2>
            <form wire:submit="create" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium mb-1.5">Board name</label>
                    <input id="name" type="text" wire:model="name" autofocus placeholder="e.g. Product Roadmap"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="description" wire:model="description" rows="2" placeholder="What is this board for?"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">Create board</button>
                    <button type="button" wire:click="$set('showCreateForm', false)" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if ($this->boards->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-16 text-center">
            <svg class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
            <p class="text-gray-600 dark:text-gray-400 font-medium">No boards yet</p>
            <p class="mt-1 text-sm text-gray-500">Create your first board to get started.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->boards as $board)
                <a href="{{ route('boards.show', $board) }}"
                    class="group relative rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 hover:ring-indigo-300 dark:hover:ring-indigo-700 transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $board->name }}</h3>
                            @if ($board->description)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $board->description }}</p>
                            @endif
                        </div>
                        <button wire:click.prevent="delete({{ $board->id }})" wire:confirm="Delete this board and all its contents?"
                            class="shrink-0 rounded p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors opacity-0 group-hover:opacity-100"
                            aria-label="Delete board">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">{{ $board->columns->count() }} columns &middot; Updated {{ $board->updated_at->diffForHumans() }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>