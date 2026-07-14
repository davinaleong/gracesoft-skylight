<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $board->name }} — {{ config('app.name', 'Skylight') }}</title>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    {{-- Viewer banner --}}
    <div class="bg-indigo-600 px-4 py-2 text-center text-sm text-white">
        Read-only view &middot; Shared by the board owner
    </div>

    @php
        $allCards = $board->columns->flatMap->cards;
    @endphp

    <div
        x-data="{ openCardId: null, lightboxUrl: null, lightboxName: '' }"
        @keydown.escape.window="if (lightboxUrl) { lightboxUrl = null; lightboxName = ''; } else { openCardId = null; }"
        class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8"
    >
        {{-- Board title --}}
        <h1 class="mb-8 text-2xl font-semibold">{{ $board->name }}</h1>

        {{-- Kanban columns (read-only) --}}
        <div class="flex gap-4 overflow-x-auto pb-6">
            @foreach ($board->columns as $column)
                <div class="shrink-0 w-72 flex flex-col rounded-xl bg-gray-100 dark:bg-gray-800/60">
                    {{-- Column header --}}
                    <div class="flex items-center justify-between px-3.5 py-3 border-b border-gray-200 dark:border-gray-700/60">
                        <h3 class="font-medium text-sm truncate">{{ $column->name }}</h3>
                        <span class="text-xs text-gray-400 bg-gray-200 dark:bg-gray-700 rounded px-1.5 py-0.5">{{ $column->cards->count() }}</span>
                    </div>

                    {{-- Cards --}}
                    <div class="flex-1 p-2 space-y-2">
                        @foreach ($column->cards as $card)
                            @php
                                $cardColor = $card->color ? \App\Models\Card::COLORS[$card->color] ?? null : null;
                            @endphp

                            <button
                                type="button"
                                @click="openCardId = {{ $card->id }}"
                                class="w-full text-left rounded-lg p-3 shadow-xs ring-1 ring-gray-200 dark:ring-gray-700 hover:ring-indigo-300 dark:hover:ring-indigo-700 transition-colors"
                                style="{{ $cardColor ? 'background-color: '.$cardColor['light'].';' : '' }}"
                            >
                                <p class="text-sm font-medium leading-snug">{{ $card->title }}</p>

                                @if ($card->description)
                                    <p class="mt-1.5 text-xs text-gray-700 line-clamp-2">{{ $card->description }}</p>
                                @endif

                                {{-- Dates --}}
                                @if ($card->starts_at || $card->ends_at)
                                    <p class="mt-2 text-xs text-gray-700">
                                        @if ($card->starts_at)
                                            Start {{ $card->starts_at->format('d M Y') }}
                                        @endif
                                        @if ($card->starts_at && $card->ends_at)
                                            &middot;
                                        @endif
                                        @if ($card->ends_at)
                                            Due {{ $card->ends_at->format('d M Y') }}
                                        @endif
                                    </p>
                                @endif

                                {{-- Labels --}}
                                @if ($card->labels->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($card->labels as $label)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white" style="background-color: {{ $label->color }}">
                                                {{ $label->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Card read-only dialog --}}
        <div
            x-show="openCardId !== null"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <button type="button" @click="openCardId = null" class="absolute inset-0 bg-black/50 backdrop-blur-sm" aria-label="Close"></button>

            <div class="relative z-10 w-full max-w-3xl max-h-[90vh] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
                @foreach ($allCards as $card)
                    @php
                        $cardColor = $card->color ? \App\Models\Card::COLORS[$card->color] ?? null : null;
                    @endphp

                    <section x-show="openCardId === {{ $card->id }}" x-cloak class="flex flex-col max-h-[90vh]">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800" style="{{ $cardColor ? 'background-color: '.$cardColor['light'].';' : '' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold">{{ $card->title }}</h2>
                                    <p class="mt-1 text-xs text-gray-600">Read-only card details</p>
                                </div>
                                <button type="button" @click="openCardId = null" class="rounded-lg p-1.5 text-gray-500 hover:text-gray-700 hover:bg-white/60" aria-label="Close">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-y-auto p-6 space-y-6">
                            {{-- Markdown description --}}
                            <div>
                                <h3 class="text-sm font-semibold mb-2">Description</h3>
                                @if ($card->description)
                                    <div class="prose prose-sm max-w-none dark:prose-invert">
                                        {!! \Illuminate\Support\Str::markdown($card->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">No description.</p>
                                @endif
                            </div>

                            {{-- Read-only dates --}}
                            <div>
                                <h3 class="text-sm font-semibold mb-2">Dates</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="text-xs text-gray-500">Start date</p>
                                        <p class="mt-1 text-sm font-medium">{{ $card->starts_at?->format('d M Y') ?? 'Not set' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="text-xs text-gray-500">Due date</p>
                                        <p class="mt-1 text-sm font-medium">{{ $card->ends_at?->format('d M Y') ?? 'Not set' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Read-only checklists accordion --}}
                            <div>
                                <h3 class="text-sm font-semibold mb-2">Checklists</h3>
                                @if ($card->checklists->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach ($card->checklists as $checklist)
                                            @php
                                                $total = $checklist->items->count();
                                                $done = $checklist->items->where('is_completed', true)->count();
                                            @endphp
                                            <details class="rounded-lg border border-gray-200 dark:border-gray-700 p-3" @if ($loop->first) open @endif>
                                                <summary class="cursor-pointer text-sm font-medium">
                                                    {{ $checklist->name }}
                                                    <span class="ml-1 text-xs text-gray-500">({{ $done }}/{{ $total }})</span>
                                                </summary>
                                                <div class="mt-2 space-y-1.5">
                                                    @forelse ($checklist->items as $item)
                                                        <div class="flex items-center gap-2 text-sm">
                                                            <span class="inline-flex h-4 w-4 items-center justify-center rounded border border-gray-300 text-[10px] {{ $item->is_completed ? 'bg-green-100 text-green-700 border-green-300' : 'text-gray-400' }}">
                                                                {{ $item->is_completed ? '✓' : '' }}
                                                            </span>
                                                            <span @class(['line-through text-gray-400' => $item->is_completed])>{{ $item->body }}</span>
                                                        </div>
                                                    @empty
                                                        <p class="text-sm text-gray-500">No items.</p>
                                                    @endforelse
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">No checklists.</p>
                                @endif
                            </div>

                            {{-- Read-only comments --}}
                            @if ($link->can_see_comments)
                                <div>
                                    <h3 class="text-sm font-semibold mb-2">Comments</h3>
                                    @if ($card->comments->isNotEmpty())
                                        <div class="space-y-2">
                                            @foreach ($card->comments as $comment)
                                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $comment->user->name }}</p>
                                                        <p class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</p>
                                                    </div>
                                                    <p class="mt-1 text-sm whitespace-pre-line">{{ $comment->body }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">No comments.</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Read-only attachments with lightbox --}}
                            @if ($link->can_see_attachments)
                                <div>
                                    <h3 class="text-sm font-semibold mb-2">Attachments</h3>
                                    @if ($card->attachments->isNotEmpty())
                                        <div class="space-y-2">
                                            @foreach ($card->attachments as $attachment)
                                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium truncate">{{ $attachment->name ?? 'Attachment' }}</p>
                                                        <p class="text-xs text-gray-500">{{ $attachment->isImage() ? 'Image' : 'Link' }}</p>
                                                    </div>

                                                    @if ($attachment->isImage())
                                                        <button
                                                            type="button"
                                                            @click="lightboxUrl = @js($attachment->temporaryUrl()); lightboxName = @js($attachment->name ?? 'Image')"
                                                            class="shrink-0 rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800"
                                                        >
                                                            View
                                                        </button>
                                                    @else
                                                        <a
                                                            href="{{ $attachment->path }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="shrink-0 rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800"
                                                        >
                                                            Open link
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">No attachments.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        {{-- Attachment lightbox --}}
        <div x-show="lightboxUrl !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <button type="button" @click="lightboxUrl = null; lightboxName = ''" class="absolute inset-0 bg-black/80" aria-label="Close image"></button>

            <figure class="relative z-10 max-w-6xl max-h-[90vh] w-full flex flex-col items-center gap-3">
                <img :src="lightboxUrl" :alt="lightboxName" class="max-h-[80vh] max-w-full object-contain rounded-lg shadow-2xl" loading="lazy">
                <figcaption class="text-sm text-white" x-text="lightboxName"></figcaption>
                <button type="button" @click="lightboxUrl = null; lightboxName = ''" class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/25">
                    Close
                </button>
            </figure>
        </div>
    </div>

    @livewireScripts
</body>
</html>
