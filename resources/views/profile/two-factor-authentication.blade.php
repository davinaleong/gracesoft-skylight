@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $enabled = ! is_null($user->two_factor_confirmed_at);
    $confirmed = $enabled;
@endphp

<div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="text-base font-semibold">Two-factor authentication</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Add an extra layer of security by requiring an authenticator app code on login.
            </p>
        </div>
        <span @class([
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' => $enabled,
            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => ! $enabled,
        ])>
            {{ $enabled ? 'Enabled' : 'Disabled' }}
        </span>
    </div>

    @if ($enabled)
        {{-- QR code and recovery codes --}}
        @if (session('status') === 'two-factor-authentication-confirmed')
            <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
                Two-factor authentication has been enabled.
            </div>
        @endif

        @if (session('status') === 'recovery-codes-generated')
            <div class="mt-4 space-y-3">
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                    Save these recovery codes in a safe place. They cannot be shown again.
                </p>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 font-mono text-sm space-y-1">
                    @foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-6 flex items-center gap-3">
            {{-- Regenerate recovery codes --}}
            <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    Regenerate recovery codes
                </button>
            </form>

            {{-- Disable 2FA --}}
            <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg border border-red-300 dark:border-red-800 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                >
                    Disable
                </button>
            </form>
        </div>
    @else
        {{-- Enable 2FA --}}
        @if ($user->two_factor_secret && ! $confirmed)
            {{-- QR code setup --}}
            <div class="mt-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Scan this QR code with your authenticator app, then enter the code below to confirm.
                </p>
                <div class="flex justify-center mb-6">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Setup key (manual entry):</p>
                <p class="mb-6 font-mono text-sm bg-gray-50 dark:bg-gray-800 rounded px-3 py-2 break-all">
                    {{ decrypt($user->two_factor_secret) }}
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="code" class="block text-sm font-medium mb-1.5">Verification code</label>
                        <input
                            id="code"
                            type="text"
                            inputmode="numeric"
                            name="code"
                            autofocus
                            autocomplete="one-time-code"
                            class="w-full max-w-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('code') border-red-500 @enderror"
                        >
                        @error('code')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Confirm and enable
                    </button>
                </form>
            </div>
        @else
            <div class="mt-4">
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        Enable two-factor authentication
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>
