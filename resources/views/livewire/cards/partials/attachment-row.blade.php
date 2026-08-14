{{-- Renders a single attachment row. Expects: $attachment, $deleteAction (Livewire method name) --}}
<div class="flex items-center gap-3 rounded-lg bg-white dark:bg-gray-900 p-3 ring-1 ring-gray-200 dark:ring-gray-800">
    @if ($attachment->isImage())
        <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left"
            @click="$dispatch('open-lightbox', { url: @js($attachment->temporaryUrl()), kind: 'image', name: @js($attachment->name ?? 'Image') })">
            <img src="{{ $attachment->temporaryUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-700">
            <span class="min-w-0 flex-1 truncate text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ $attachment->name ?? 'Image' }}
            </span>
        </button>
    @elseif ($attachment->isDocument())
        <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left"
            @click="$dispatch('open-lightbox', { url: @js($attachment->temporaryUrl()), kind: 'pdf', name: @js($attachment->name ?? 'Document') })">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20 ring-1 ring-gray-200 dark:ring-gray-700">
                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </span>
            <span class="min-w-0 flex-1 truncate text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ $attachment->name ?? 'Document' }}
            </span>
        </button>
    @else
        <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
        <a href="{{ $attachment->path }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 truncate text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
            {{ $attachment->name }}
        </a>
    @endif
    @if ($attachment->user_id === auth()->id())
        <button wire:click="{{ $deleteAction }}({{ $attachment->id }})" wire:confirm="Remove this attachment?"
            class="shrink-0 text-gray-400 hover:text-red-500 transition-colors" aria-label="Delete attachment">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    @endif
</div>
