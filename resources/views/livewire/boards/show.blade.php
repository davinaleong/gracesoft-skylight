<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use function Livewire\Volt\{state, computed, mount};

state([
    'board' => null,
    'showColumnForm' => false,
    'newColumnName' => '',
    'addingCardToColumn' => null,
    'newCardTitle' => '',
    'editingCard' => null,
    'editCardTitle' => '',
    'editCardDescription' => '',
]);

mount(function (Board $board) {
    $this->board = $board;
});

$columns = computed(fn () => $this->board->columns()->with('cards')->get());

$createColumn = function () {
    $this->validate(['newColumnName' => ['required', 'string', 'max:255']], attributes: ['newColumnName' => 'column name']);

    $this->board->columns()->create([
        'name' => $this->newColumnName,
        'position' => $this->board->columns()->count(),
    ]);

    $this->reset('newColumnName', 'showColumnForm');
};

$deleteColumn = function (int $columnId) {
    $this->board->columns()->findOrFail($columnId)->delete();
};

$createCard = function (int $columnId) {
    $this->validate(['newCardTitle' => ['required', 'string', 'max:255']], attributes: ['newCardTitle' => 'card title']);

    $column = $this->board->columns()->findOrFail($columnId);
    $column->cards()->create([
        'title' => $this->newCardTitle,
        'position' => $column->cards()->count(),
    ]);

    $this->reset('newCardTitle', 'addingCardToColumn');
};

$deleteCard = function (int $cardId) {
    Card::whereHas('column', fn ($q) => $q->where('board_id', $this->board->id))
        ->findOrFail($cardId)
        ->delete();
};

$startEditCard = function (int $cardId) {
    $card = Card::whereHas('column', fn ($q) => $q->where('board_id', $this->board->id))
        ->findOrFail($cardId);

    $this->editingCard = $card->id;
    $this->editCardTitle = $card->title;
    $this->editCardDescription = $card->description ?? '';
};

$saveCard = function () {
    $this->validate([
        'editCardTitle' => ['required', 'string', 'max:255'],
        'editCardDescription' => ['nullable', 'string'],
    ]);

    Card::whereHas('column', fn ($q) => $q->where('board_id', $this->board->id))
        ->findOrFail($this->editingCard)
        ->update(['title' => $this->editCardTitle, 'description' => $this->editCardDescription]);

    $this->reset('editingCard', 'editCardTitle', 'editCardDescription');
};

$updateColumnOrder = function (array $orderedIds) {
    foreach ($orderedIds as $position => $id) {
        $this->board->columns()->where('id', $id)->update(['position' => $position]);
    }
};

$updateCardOrder = function (int $columnId, array $orderedIds) {
    $column = $this->board->columns()->findOrFail($columnId);
    foreach ($orderedIds as $position => $id) {
        $column->cards()->where('id', $id)->update(['position' => $position, 'column_id' => $columnId]);
    }
};

$moveCard = function (int $cardId, int $toColumnId, int $position) {
    $toColumn = $this->board->columns()->findOrFail($toColumnId);

    Card::whereHas('column', fn ($q) => $q->where('board_id', $this->board->id))
        ->findOrFail($cardId)
        ->update(['column_id' => $toColumnId, 'position' => $position]);
};

?>

<div>
    {{-- Board header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-2xl font-semibold">{{ $board->name }}</h1>
        </div>
        <button
            wire:click="$set('showColumnForm', true)"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 px-3.5 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add column
        </button>
    </div>

    {{-- Kanban board --}}
    <div
        class="flex gap-4 overflow-x-auto pb-6"
        x-data="kanbanBoard($wire)"
        x-init="initColumns()"
        wire:ignore.self
    >
        @foreach ($this->columns as $column)
            <div
                class="shrink-0 w-72 flex flex-col rounded-xl bg-gray-100 dark:bg-gray-800/60"
                data-column-id="{{ $column->id }}"
            >
                {{-- Column header --}}
                <div class="flex items-center justify-between px-3.5 py-3 border-b border-gray-200 dark:border-gray-700/60">
                    <h3 class="font-medium text-sm truncate">{{ $column->name }}</h3>
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-gray-400 bg-gray-200 dark:bg-gray-700 rounded px-1.5 py-0.5">{{ $column->cards->count() }}</span>
                        <button
                            wire:click="deleteColumn({{ $column->id }})"
                            wire:confirm="Delete this column and all its cards?"
                            class="rounded p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            aria-label="Delete column"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Cards --}}
                <div
                    class="flex-1 p-2 space-y-2 min-h-12"
                    data-sortable-cards
                    data-column-id="{{ $column->id }}"
                >
                    @foreach ($column->cards as $card)
                        <div
                            class="rounded-lg bg-white dark:bg-gray-900 p-3 shadow-xs ring-1 ring-gray-200 dark:ring-gray-700 cursor-grab active:cursor-grabbing select-none"
                            data-card-id="{{ $card->id }}"
                        >
                            @if ($editingCard === $card->id)
                                <form wire:submit="saveCard" class="space-y-2">
                                    <input
                                        type="text"
                                        wire:model="editCardTitle"
                                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                    <textarea
                                        wire:model="editCardDescription"
                                        rows="2"
                                        placeholder="Descriptionâ€¦"
                                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                    ></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="text-xs rounded bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 font-medium">Save</button>
                                        <button type="button" wire:click="$set('editingCard', null)" class="text-xs rounded border border-gray-300 dark:border-gray-700 px-2.5 py-1">Cancel</button>
                                    </div>
                                </form>
                            @else
                                <div class="flex items-start justify-between gap-1.5">
                                    <p class="text-sm leading-snug flex-1">{{ $card->title }}</p>
                                    <div class="flex shrink-0 gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button wire:click="startEditCard({{ $card->id }})" class="rounded p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Edit card">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                        </button>
                                        <button wire:click="deleteCard({{ $card->id }})" wire:confirm="Delete this card?" class="rounded p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" aria-label="Delete card">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </div>
                                @if ($card->description)
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $card->description }}</p>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Add card --}}
                <div class="p-2 border-t border-gray-200 dark:border-gray-700/60">
                    @if ($addingCardToColumn === $column->id)
                        <form wire:submit="createCard({{ $column->id }})" class="space-y-2">
                            <input
                                type="text"
                                wire:model="newCardTitle"
                                autofocus
                                placeholder="Card titleâ€¦"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                            @error('newCardTitle')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="flex gap-2">
                                <button type="submit" class="text-xs rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 font-medium">Add card</button>
                                <button type="button" wire:click="$set('addingCardToColumn', null)" class="text-xs rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5">Cancel</button>
                            </div>
                        </form>
                    @else
                        <button
                            wire:click="$set('addingCardToColumn', {{ $column->id }})"
                            class="w-full flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add card
                        </button>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Add column form --}}
        @if ($showColumnForm)
            <div class="shrink-0 w-72 rounded-xl bg-gray-100 dark:bg-gray-800/60 p-3">
                <form wire:submit="createColumn" class="space-y-2">
                    <input
                        type="text"
                        wire:model="newColumnName"
                        autofocus
                        placeholder="Column nameâ€¦"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                    @error('newColumnName')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex gap-2">
                        <button type="submit" class="text-xs rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 font-medium">Add column</button>
                        <button type="button" wire:click="$set('showColumnForm', false)" class="text-xs rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5">Cancel</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

@script
<script>
    Alpine.data('kanbanBoard', ($wire) => ({
        sortableColumns: null,
        cardSortables: [],

        initColumns() {
            const boardEl = this.$el;

            // Column drag-and-drop
            this.sortableColumns = new Sortable(boardEl, {
                animation: 150,
                handle: '.font-medium',
                draggable: '[data-column-id]',
                ghostClass: 'opacity-30',
                onEnd: (evt) => {
                    const ids = [...boardEl.querySelectorAll('[data-column-id]')]
                        .map(el => parseInt(el.dataset.columnId));
                    $wire.updateColumnOrder(ids);
                }
            };

            this.$watch('$wire.columns', () => {
                this.$nextTick(() => this.initCards());
            };

            this.initCards();
        },

        initCards() {
            this.cardSortables.forEach(s => s.destroy());
            this.cardSortables = [];

            document.querySelectorAll('[data-sortable-cards]').forEach(container => {
                const sortable = new Sortable(container, {
                    group: 'cards',
                    animation: 150,
                    ghostClass: 'opacity-30',
                    onEnd: (evt) => {
                        const toColumnId = parseInt(evt.to.dataset.columnId);
                        const cardId = parseInt(evt.item.dataset.cardId);
                        const orderedIds = [...evt.to.querySelectorAll('[data-card-id]')]
                            .map(el => parseInt(el.dataset.cardId));
                        $wire.moveCard(cardId, toColumnId, orderedIds.indexOf(cardId));
                        $wire.updateCardOrder(toColumnId, orderedIds);
                    }
                };
                this.cardSortables.push(sortable);
            };
        }
    }));
</script>
@endscript



