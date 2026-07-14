<x-layouts.auth title="Verify email — {{ config('app.name', 'Skylight') }}">
    <h1 class="mb-2 text-center text-xl font-semibold">Verify your email</h1>
    <p class="mb-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Before continuing, please verify your email address using the link we just sent.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            A fresh verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf
        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button
            type="submit"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            Sign out
        </button>
    </form>
</x-layouts.auth>
