<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'Skylight') }}</title>

    <link rel="icon" href="{{ asset('logo.svg') }}" type="image/xml+svg">

    {{-- Apply dark class before first paint to avoid flash --}}
    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    <nav class="border-b border-gray-200 dark:border-gray-800">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('wm.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 dark:hidden">
                <img src="{{ asset('wm-w.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 hidden dark:block">
            </a>
            <a href="{{ auth()->check() ? route('home') : route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                {{ auth()->check() ? 'Go to app' : 'Sign in' }}
            </a>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        {{ $slot }}
    </main>
</body>
</html>
