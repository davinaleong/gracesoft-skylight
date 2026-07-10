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
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="mb-8">
            <a href="{{ url('/') }}" class="block">
                <img src="{{ asset('wm.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-10 dark:hidden">
                <img src="{{ asset('wm-w.svg') }}" alt="{{ config('app.name', 'Skylight') }}" class="h-10 hidden dark:block">
            </a>
        </div>

        <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
