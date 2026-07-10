<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $board->name }} — {{ config('app.name', 'Skylight') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    {{-- Viewer banner --}}
    <div class="bg-indigo-600 px-4 py-2 text-center text-sm text-white">
        Read-only view &middot; Shared by the board owner
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
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
                            <div class="rounded-lg bg-white dark:bg-gray-900 p-3 shadow-xs ring-1 ring-gray-200 dark:ring-gray-700">
                                <p class="text-sm font-medium leading-snug">{{ $card->title }}</p>

                                @if ($card->description)
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $card->description }}</p>
                                @endif

                                {{-- Dates --}}
                                @if ($card->ends_at)
                                    <p @class([
                                        'mt-2 text-xs font-medium',
                                        'text-red-600 dark:text-red-400' => $card->ends_at->isPast(),
                                        'text-gray-500 dark:text-gray-400' => ! $card->ends_at->isPast(),
                                    ])>
                                        Due {{ $card->ends_at->format('d M Y') }}
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

                                {{-- Comments (if allowed) --}}
                                @if ($link->can_see_comments && $card->comments->isNotEmpty())
                                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 space-y-1.5">
                                        @foreach ($card->comments->take(3) as $comment)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $comment->user->name }}</span>:
                                                {{ Str::limit($comment->body, 120) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
