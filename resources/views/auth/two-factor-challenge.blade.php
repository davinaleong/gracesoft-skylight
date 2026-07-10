<x-layouts.auth title="Two-factor authentication — {{ config('app.name', 'Skylight') }}">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
        </div>
        <h1 class="text-xl font-semibold">Two-factor authentication</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-data="{ recovery: false }" x-cloak>
            <span x-show="!recovery">Enter the code from your authenticator app.</span>
            <span x-show="recovery">Enter one of your emergency recovery codes.</span>
        </p>
    </div>

    <div x-data="{ recovery: false }" x-cloak>
        {{-- TOTP code form --}}
        <form method="POST" action="{{ route('two-factor.login') }}" x-show="!recovery" class="space-y-5">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium mb-1.5">Authentication code</label>
                <input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    name="code"
                    autofocus
                    autocomplete="one-time-code"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 @error('code') border-red-500 @enderror"
                >
                @error('code')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Verify
            </button>
        </form>

        {{-- Recovery code form --}}
        <form method="POST" action="{{ route('two-factor.login') }}" x-show="recovery" class="space-y-5">
            @csrf

            <div>
                <label for="recovery_code" class="block text-sm font-medium mb-1.5">Recovery code</label>
                <input
                    id="recovery_code"
                    type="text"
                    name="recovery_code"
                    autocomplete="one-time-code"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm font-mono shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 @error('recovery_code') border-red-500 @enderror"
                >
                @error('recovery_code')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Verify recovery code
            </button>
        </form>

        <div class="mt-4 text-center">
            <button
                type="button"
                @click="recovery = !recovery"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
            >
                <span x-show="!recovery">Use a recovery code instead</span>
                <span x-show="recovery">Use an authenticator app instead</span>
            </button>
        </div>
    </div>
</x-layouts.auth>
