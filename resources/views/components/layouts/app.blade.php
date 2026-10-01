<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? config('app.name', 'Skylight') }}</title>

    <link rel="icon" href="{{ asset('logo.svg') }}" type="image/xml+svg" >

    @feature(\App\Features\M0Foundations::class)
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        <meta name="theme-color" content="#372aac">
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    @endfeature

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
    @livewireStyles
</head>
<body
    class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased"
    x-data="{ showShortcuts: false }"
    @keydown.window="
        const tag = $event.target.tagName;
        const typing = tag === 'INPUT' || tag === 'TEXTAREA' || $event.target.isContentEditable;

        if ($event.key === '/' && !typing) {
            $event.preventDefault();
            document.getElementById('global-search-input')?.focus();
        } else if ($event.key === '?' && !typing) {
            $event.preventDefault();
            showShortcuts = true;
        } else if ($event.key === 'Escape') {
            showShortcuts = false;
        }
    "
>
    {{-- Keyboard shortcuts help overlay --}}
    <div
        x-show="showShortcuts"
        x-cloak
        @click.self="showShortcuts = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold">Keyboard shortcuts</h2>
                <button @click="showShortcuts = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <dl class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Focus search</dt>
                    <dd><kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 font-mono text-xs">/</kbd></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Quick-add a card (on a board)</dt>
                    <dd><kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 font-mono text-xs">c</kbd></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Close dialog / cancel</dt>
                    <dd><kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 font-mono text-xs">Esc</kbd></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Show this help</dt>
                    <dd><kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 font-mono text-xs">?</kbd></dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Top navigation --}}
    <nav class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-3 py-2.5 sm:flex-nowrap sm:gap-4">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                    <img src="{{ asset('wm.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 dark:hidden">
                    <img src="{{ asset('wm-w.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-7 hidden dark:block">
                </a>

                {{-- Actions --}}
                <div x-data="{
                    dark: document.documentElement.classList.contains('dark'),
                    toggle() {
                        this.dark = !this.dark;
                        document.documentElement.classList.toggle('dark', this.dark);
                        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    }
                }" class="order-2 ml-auto flex shrink-0 items-center gap-2 sm:order-none sm:gap-3">
                    <button @click="toggle()" class="rounded-lg p-1.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'">
                        <svg x-show="!dark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                        <svg x-show="dark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                    </button>
                    <livewire:notifications.bell />
                    <a href="{{ route('team') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                        Team
                    </a>
                    <a href="{{ route('billing') }}" class="hidden text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors sm:inline">
                        Billing
                    </a>
                    <a href="{{ route('webhooks') }}" class="hidden text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors sm:inline">
                        Webhooks
                    </a>
                    <a href="{{ route('integrations') }}" class="hidden text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors sm:inline">
                        Integrations
                    </a>
                    <a href="{{ route('profile') }}" class="hidden text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors sm:inline">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            Sign out
                        </button>
                    </form>
                </div>

                {{-- Search — full width on its own row on mobile, fills remaining space on larger screens --}}
                <div class="order-3 w-full sm:order-none sm:min-w-0 sm:flex-1">
                    <livewire:search.global />
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
