{{-- Item-level "add attachment" trigger + mini form. Expects: $type ('checklist'|'comment'|'note'), $id --}}
@if ($attachTargetType === $type && $attachTargetId === $id)
    <div class="space-y-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
        <form wire:submit="uploadItemFile" class="space-y-1.5">
            <input type="file" wire:model="itemFileUpload" accept="image/*,application/pdf"
                class="block w-full text-xs text-gray-600 file:mr-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-2.5 file:py-1 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
            @error('itemFileUpload') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @if ($itemFileUpload)
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 text-xs font-medium text-white">
                    Upload
                </button>
            @endif
        </form>

        @if ($showItemLinkForm)
            <form wire:submit="addItemLink" class="space-y-1.5">
                <input type="url" wire:model="itemLinkUrl" placeholder="https://example.com"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('itemLinkUrl') border-red-500 @enderror">
                @error('itemLinkUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <input type="text" wire:model="itemLinkName" placeholder="Label (optional)"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 text-xs font-medium text-white">Add link</button>
            </form>
        @else
            <button type="button" wire:click="$set('showItemLinkForm', true)" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                + Add link
            </button>
        @endif

        <button type="button" wire:click="closeAttachmentForm" class="block text-xs text-gray-500 hover:underline">
            Cancel
        </button>
    </div>
@else
    <button type="button" wire:click="openAttachmentForm('{{ $type }}', {{ $id }})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
        + Add attachment
    </button>
@endif
