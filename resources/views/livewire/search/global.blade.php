<?php

use App\Models\Board;
use App\Models\Card;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $query = '';
    public bool $open = false;

    #[Computed]
    public function results()
    {
        if (strlen($this->query) < 2) {
            return ['boards' => collect(), 'cards' => collect()];
        }

        $userId = auth()->id();

        $boards = Board::where('user_id', $userId)
            ->where('name', 'like', '%'.$this->query.'%')
            ->limit(5)
            ->get();

        $cards = Card::whereHas('column.board', fn ($q) => $q->where('user_id', $userId))
            ->where('title', 'like', '%'.$this->query.'%')
            ->with('column.board')
            ->limit(8)
            ->get();

        return compact('boards', 'cards');
    }
};
?>

<div x-data="{ open: @entangle('open') }" @keydown.escape.window="open = false" class="relative">
    {{-- Search input --}}
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input
            type="search"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            placeholder="Search boards & cards&hellip;"
            class="w-64 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 pl-9 pr-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
    </div>

    {{-- Results dropdown --}}
    @if ($open && strlen($query) >= 2)
        <div
            @click.outside="open = false"
            class="absolute left-0 top-full z-50 mt-1.5 w-96 rounded-xl bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden"
        >
            @php $results = $this->results; @endphp

            @if ($results['boards']->isEmpty() && $results['cards']->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-500">No results for &ldquo;{{ $query }}&rdquo;</p>
            @else
                @if ($results['boards']->isNotEmpty())
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        Boards
                    </div>
                    @foreach ($results['boards'] as $board)
                        <a
                            href="{{ route('boards.show', $board) }}"
                            wire:navigate
                            @click="open = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        >
                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                            <span class="font-medium">{{ $board->name }}</span>
                        </a>
                    @endforeach
                @endif

                @if ($results['cards']->isNotEmpty())
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-t border-gray-100 dark:border-gray-800">
                        Cards
                    </div>
                    @foreach ($results['cards'] as $card)
                        <a
                            href="{{ route('boards.show', $card->column->board) }}"
                            wire:navigate
                            @click="open = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        >
                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6" /></svg>
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $card->title }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $card->column->board->name }} &rsaquo; {{ $card->column->name }}</p>
                            </div>
                        </a>
                    @endforeach
                @endif
            @endif
        </div>
    @endif
</div>

</div>
