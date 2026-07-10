<x-layouts.app title="Profile — {{ config('app.name', 'Skylight') }}">
    <div class="max-w-2xl space-y-6">
        <h1 class="text-2xl font-semibold">Profile settings</h1>

        {{-- Update profile information --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="mb-4 text-base font-semibold">Account information</h3>

            @if (session('status') === 'profile-information-updated')
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
                    Profile updated successfully.
                </div>
            @endif

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium mb-1.5">Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', auth()->user()->name) }}"
                        required
                        autocomplete="name"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                    >
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium mb-1.5">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', auth()->user()->email) }}"
                        required
                        autocomplete="username"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Update password --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="mb-4 text-base font-semibold">Change password</h3>

            @if (session('status') === 'password-updated')
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
                    Password updated successfully.
                </div>
            @endif

            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium mb-1.5">Current password</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1.5">New password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-1.5">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Update password
                    </button>
                </div>
            </form>
        </div>

        {{-- Two-factor authentication --}}
        @include('profile.two-factor-authentication')
    </div>
</x-layouts.app>
