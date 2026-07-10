<x-layouts.auth title="Forgot password — {{ config('app.name', 'Skylight') }}">
    <h1 class="mb-2 text-center text-xl font-semibold">Forgot your password?</h1>
    <p class="mb-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Enter your email and we'll send you a reset link.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

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

        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Send reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Back to sign in</a>
    </p>
</x-layouts.auth>
