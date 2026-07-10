<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? config('app.name', 'Skylight') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    {{-- Top navigation --}}
    <nav class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('wm.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 dark:hidden" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                    <span class="hidden text-lg font-semibold tracking-tight dark:block">{{ config('app.name', 'Skylight') }}</span>
                </a>

                <div class="flex items-center gap-4">
                    <a href="{{ route('profile') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main content --}}
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
