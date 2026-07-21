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
            <a href="{{ auth()->check() ? route('home') : route('landing') }}" class="flex items-center gap-2">
                <img src="{{ asset('wm.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 dark:hidden">
                <img src="{{ asset('wm-w.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 hidden dark:block">
            </a>
            <div class="flex items-center gap-5">
                <a href="{{ route('pricing') }}" class="hidden sm:inline text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    Pricing
                </a>
                <a href="{{ auth()->check() ? route('home') : route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    {{ auth()->check() ? 'Go to app' : 'Sign in' }}
                </a>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-800 mt-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
            <span>&copy; {{ now()->year }} {{ config('app.name', 'Skylight') }}</span>
            <a href="{{ route('pricing') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Pricing</a>
            <a href="{{ route('changelog') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Changelog</a>
            <a href="{{ route('status') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Status</a>
            <a href="{{ route('security') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Security</a>
            <a href="{{ route('privacy') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Terms</a>
        </div>
    </footer>
</body>
</html>
