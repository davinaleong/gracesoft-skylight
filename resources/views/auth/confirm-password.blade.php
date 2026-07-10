<x-layouts.auth title="Confirm password — {{ config('app.name', 'Skylight') }}">
    <h1 class="mb-2 text-center text-xl font-semibold">Confirm your password</h1>
    <p class="mb-6 text-center text-sm text-gray-600 dark:text-gray-400">
        This is a secure area. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium mb-1.5">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 @error('password') border-red-500 @enderror"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Confirm
        </button>
    </form>
</x-layouts.auth>
