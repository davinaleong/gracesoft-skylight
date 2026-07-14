<x-layouts.auth title="Workspace invitation — {{ config('app.name', 'Skylight') }}">
    <h1 class="mb-2 text-center text-xl font-semibold">You're invited</h1>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    @if ($invite->isAccepted())
        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
            This invitation to <strong>{{ $invite->workspace->name }}</strong> has already been accepted.
        </p>
        <a href="{{ route('home') }}" class="mt-6 block w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-center text-sm font-medium text-white shadow-xs transition-colors">
            Go to your boards
        </a>
    @elseif ($invite->isExpired())
        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
            This invitation to <strong>{{ $invite->workspace->name }}</strong> has expired. Ask
            {{ $invite->inviter?->name ?? 'the workspace owner' }} to send you a new one.
        </p>
    @else
        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
            <strong>{{ $invite->inviter?->name ?? 'A teammate' }}</strong> invited
            <strong>{{ $invite->email }}</strong> to join
            <strong>{{ $invite->workspace->name }}</strong> as a <strong>{{ $invite->role }}</strong>.
        </p>

        @auth
            @if (str(auth()->user()->email)->lower()->is(str($invite->email)->lower()))
                <form method="POST" action="{{ route('invites.accept', $token) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Accept invitation
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-700 dark:text-amber-400">
                    You're signed in as {{ auth()->user()->email }}, but this invite was sent to {{ $invite->email }}.
                    Sign out and sign in with the invited address to accept.
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        Sign out
                    </button>
                </form>
            @endif
        @else
            <div class="mt-6 space-y-3">
                <a href="{{ route('register', ['email' => $invite->email]) }}" class="block w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-center text-sm font-medium text-white shadow-xs transition-colors">
                    Create an account
                </a>
                <a href="{{ route('login', ['email' => $invite->email]) }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2.5 text-center text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    Sign in
                </a>
            </div>
            <p class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                After signing in, come back to this link to accept the invitation.
            </p>
        @endauth
    @endif
</x-layouts.auth>
