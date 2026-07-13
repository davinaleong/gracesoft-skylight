<?php

use App\Models\Attachment;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\MarkdownNote;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

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

    // Attachments — image upload
    public $imageUpload = null;

    // Attachments — link
    public string $linkUrl = '';
    public string $linkName = '';
    public bool $showLinkForm = false;

    // Markdown notes
    public string $noteName = '';
    public string $noteContent = '';
    public ?int $editingNoteId = null;
    public bool $showNoteForm = false;

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
            'endsAt' => ['nullable', 'date', Rule::when($this->startsAt !== '', ['after_or_equal:startsAt'])],
        ]);

        $this->card->update([
            'starts_at' => $this->startsAt ?: null,
            'ends_at' => $this->endsAt ?: null,
        ]);

        $this->card->refresh();
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

    // ─── Attachments ────────────────────────────────────────────────────────

    #[Computed]
    public function attachments()
    {
        return $this->card->attachments()->with('user')->get();
    }

    #[Computed]
    public function markdownNotes()
    {
        return $this->card->markdownNotes()->with('user')->get();
    }

    public function uploadImage(): void
    {
        $this->validate([
            'imageUpload' => ['required', 'image', 'max:10240'], // 10 MB
        ]);

        $path = $this->imageUpload->store('attachments', config('filesystems.default'));

        $this->card->attachments()->create([
            'user_id' => auth()->id(),
            'type' => Attachment::TYPE_IMAGE,
            'path' => $path,
            'name' => $this->imageUpload->getClientOriginalName(),
            'mime_type' => $this->imageUpload->getMimeType(),
            'size' => $this->imageUpload->getSize(),
        ]);

        $this->reset('imageUpload');
    }

    public function addLink(): void
    {
        $this->validate([
            'linkUrl' => ['required', 'url', 'max:2048'],
            'linkName' => ['nullable', 'string', 'max:255'],
        ]);

        $this->card->attachments()->create([
            'user_id' => auth()->id(),
            'type' => Attachment::TYPE_LINK,
            'path' => $this->linkUrl,
            'name' => $this->linkName ?: $this->linkUrl,
        ]);

        $this->reset('linkUrl', 'linkName', 'showLinkForm');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = $this->card->attachments()
            ->where('user_id', auth()->id())
            ->findOrFail($attachmentId);

        if ($attachment->isImage()) {
            \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))
                ->delete($attachment->path);
        }

        $attachment->delete();
    }

    public function saveNote(): void
    {
        $this->validate([
            'noteName' => ['required', 'string', 'max:255'],
            'noteContent' => ['required', 'string'],
        ]);

        if ($this->editingNoteId) {
            $this->card->markdownNotes()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingNoteId)
                ->update(['name' => $this->noteName, 'content' => $this->noteContent]);
        } else {
            $this->card->markdownNotes()->create([
                'user_id' => auth()->id(),
                'name' => $this->noteName,
                'content' => $this->noteContent,
            ]);
        }

        $this->reset('noteName', 'noteContent', 'editingNoteId', 'showNoteForm');
    }

    public function editNote(int $noteId): void
    {
        $note = $this->card->markdownNotes()
            ->where('user_id', auth()->id())
            ->findOrFail($noteId);

        $this->editingNoteId = $note->id;
        $this->noteName = $note->name;
        $this->noteContent = $note->content;
        $this->showNoteForm = true;
    }

    public function deleteNote(int $noteId): void
    {
        $this->card->markdownNotes()
            ->where('user_id', auth()->id())
            ->findOrFail($noteId)
            ->delete();
    }
};
?>

<div class="space-y-6">
    {{-- Card title --}}
    <div @if ($card->color) data-card-color="{{ $card->color }}" @endif class="rounded-lg px-3 py-2 -mx-3 -mt-2 {{ $card->color ? '' : '' }}">
        <h2 class="text-lg font-semibold">{{ $card->title }}</h2>
        @if ($card->description)
            <div class="mt-1 card-prose text-sm text-gray-600 dark:text-gray-400">
                {!! Str::markdown($card->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
            </div>
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

    {{-- Attachments --}}
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Attachments</h3>

        {{-- Existing attachments --}}
        @foreach ($this->attachments as $attachment)
            <div class="flex items-center gap-3 rounded-lg bg-white dark:bg-gray-900 p-3 ring-1 ring-gray-200 dark:ring-gray-800">
                @if ($attachment->isImage())
                    <svg class="h-5 w-5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <a href="{{ $attachment->temporaryUrl() }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 truncate text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $attachment->name ?? 'Image' }}
                    </a>
                @else
                    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                    <a href="{{ $attachment->path }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 truncate text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $attachment->name }}
                    </a>
                @endif
                @if ($attachment->user_id === auth()->id())
                    <button wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Remove this attachment?"
                        class="shrink-0 text-gray-400 hover:text-red-500 transition-colors" aria-label="Delete attachment">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                @endif
            </div>
        @endforeach

        {{-- Image upload --}}
        <form wire:submit="uploadImage" class="space-y-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Upload image</label>
                <input type="file" wire:model="imageUpload" accept="image/*"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
                @error('imageUpload') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($imageUpload)
                <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white">
                    Upload
                </button>
            @endif
        </form>

        {{-- Link attachment --}}
        @if ($showLinkForm)
            <form wire:submit="addLink" class="space-y-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">URL</label>
                    <input type="url" wire:model="linkUrl" placeholder="https://example.com"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('linkUrl') border-red-500 @enderror">
                    @error('linkUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label (optional)</label>
                    <input type="text" wire:model="linkName" placeholder="Link description"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white">Add link</button>
                    <button type="button" wire:click="$set('showLinkForm', false)" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs">Cancel</button>
                </div>
            </form>
        @else
            <button wire:click="$set('showLinkForm', true)" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                + Add link
            </button>
        @endif
    </div>

    {{-- Markdown Notes --}}
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Markdown Notes</h3>

        @foreach ($this->markdownNotes as $note)
            <div class="rounded-lg bg-white dark:bg-gray-900 p-3.5 ring-1 ring-gray-200 dark:ring-gray-800">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-sm font-medium">{{ $note->name }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-400">{{ $note->created_at->format('d M Y') }}</span>
                        @if ($note->user_id === auth()->id())
                            <button wire:click="editNote({{ $note->id }})" class="text-gray-400 hover:text-indigo-600 transition-colors" aria-label="Edit note">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                            </button>
                            <button wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" class="text-gray-400 hover:text-red-500 transition-colors" aria-label="Delete note">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-3 whitespace-pre-line">{{ $note->content }}</p>
            </div>
        @endforeach

        @if ($showNoteForm)
            <form wire:submit="saveNote" class="space-y-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Note title</label>
                    <input type="text" wire:model="noteName" placeholder="Title&hellip;"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('noteName') border-red-500 @enderror">
                    @error('noteName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Content (Markdown)</label>
                    <textarea wire:model="noteContent" rows="5" placeholder="## Notes&#10;&#10;Write **markdown** here..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y @error('noteContent') border-red-500 @enderror"></textarea>
                    @error('noteContent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white">
                        {{ $editingNoteId ? 'Update note' : 'Save note' }}
                    </button>
                    <button type="button" wire:click="$set('showNoteForm', false); $set('editingNoteId', null)" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs">Cancel</button>
                </div>
            </form>
        @else
            <button wire:click="$set('showNoteForm', true)" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                + New note
            </button>
        @endif
    </div>
</div>

</div>
