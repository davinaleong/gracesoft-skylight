<?php

use App\Features\M0Foundations;
use App\Notifications\Account\AccountDeletionScheduledNotification;
use App\Services\AccountDeletion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    public string $twoFactorCode = '';

    public bool $confirming = false;

    #[Computed]
    public function blockers(): array
    {
        return AccountDeletion::blockers(auth()->user());
    }

    #[Computed]
    public function requiresTwoFactor(): bool
    {
        return auth()->user()->two_factor_confirmed_at !== null;
    }

    public function scheduleDeletion(): void
    {
        abort_unless(Feature::active(M0Foundations::class), 403);

        $user = auth()->user();

        $this->validate([
            'password' => ['required', 'string'],
            'twoFactorCode' => [$this->requiresTwoFactor ? 'required' : 'nullable', 'string'],
        ]);

        $throttleKey = 'delete-account:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('password', 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.');

            return;
        }

        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->addError('password', 'That password is incorrect.');

            return;
        }

        if ($this->requiresTwoFactor && ! $this->verifyTwoFactor($user, trim($this->twoFactorCode))) {
            RateLimiter::hit($throttleKey);
            $this->addError('twoFactorCode', 'That authentication or recovery code is invalid.');

            return;
        }

        RateLimiter::clear($throttleKey);

        if ($this->blockers !== []) {
            return;
        }

        AccountDeletion::schedule($user);
        $user->notify(new AccountDeletionScheduledNotification);

        $this->reset('password', 'twoFactorCode', 'confirming');
    }

    public function cancelDeletion(): void
    {
        AccountDeletion::cancel(auth()->user());
    }

    /**
     * Accept a current TOTP code, or consume a recovery code (which also
     * triggers the existing "recovery code used" security email).
     */
    private function verifyTwoFactor($user, string $code): bool
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        if (app(TwoFactorAuthenticationProvider::class)->verify($secret, $code)) {
            return true;
        }

        if (in_array($code, $user->recoveryCodes(), true)) {
            $user->replaceRecoveryCode($code);

            return true;
        }

        return false;
    }
};
?>

<div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-red-200 dark:ring-red-900/50">
    <h3 class="mb-1 text-base font-semibold text-red-700 dark:text-red-400">Delete account</h3>

    @if (auth()->user()->deletion_scheduled_at)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3.5 text-sm text-red-800 dark:text-red-300">
            Your account will be permanently deleted on
            <strong>{{ auth()->user()->deletion_scheduled_at->format('M j, Y \a\t H:i') }}</strong>.
            <button wire:click="cancelDeletion" class="ml-1 font-medium underline hover:no-underline">Keep my account</button>
        </div>
    @else
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Permanently delete your account, every workspace you own (with its boards and files), and your comments and notes elsewhere. You'll have {{ \App\Services\AccountDeletion::GRACE_PERIOD_DAYS }} days to change your mind. Boards you created in other people's workspaces stay with that workspace.
        </p>

        @if ($this->blockers !== [])
            <ul class="mb-4 list-disc space-y-1 pl-5 text-sm text-amber-700 dark:text-amber-400">
                @foreach ($this->blockers as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        @elseif (! $confirming)
            <button
                wire:click="$set('confirming', true)"
                class="rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 shadow-xs ring-1 ring-red-300 dark:ring-red-900 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
            >
                Delete my account…
            </button>
        @else
            <form wire:submit="scheduleDeletion" class="space-y-4">
                <div>
                    <label for="deletePassword" class="block text-sm font-medium mb-1.5">Your password</label>
                    <input
                        id="deletePassword"
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-red-500 @error('password') border-red-500 @enderror"
                    >
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @if ($this->requiresTwoFactor)
                    <div>
                        <label for="deleteTwoFactorCode" class="block text-sm font-medium mb-1.5">Authentication or recovery code</label>
                        <input
                            id="deleteTwoFactorCode"
                            type="text"
                            wire:model="twoFactorCode"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-red-500 @error('twoFactorCode') border-red-500 @enderror"
                        >
                        @error('twoFactorCode')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-red-600 hover:bg-red-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Schedule deletion
                    </button>
                    <button type="button" wire:click="$set('confirming', false)" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:underline">
                        Cancel
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
