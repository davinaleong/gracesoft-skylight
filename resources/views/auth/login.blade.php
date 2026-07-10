<x-layouts.auth title="Sign in — {{ config('app.name', 'Skylight') }}">
    <h1 class="mb-6 text-center text-xl font-semibold">Sign in to your account</h1>

    {{-- Session status --}}
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium mb-1.5">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 @error('email') border-red-500 @enderror"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        Forgot your password?
                    </a>
                @endif
            </div>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2">
            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <label for="remember" class="text-sm text-gray-600 dark:text-gray-400">Remember me</label>
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Sign in
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>
        </p>
    @endif
</x-layouts.auth>
