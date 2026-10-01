<?php

use App\Features\M0Foundations;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Volt\Component;

new class extends Component {
    /** @var array<string, bool> */
    public array $preferences = [];

    public bool $saved = false;

    public function mount(): void
    {
        $user = auth()->user();

        foreach (array_keys(User::NOTIFICATION_PREFERENCES) as $key) {
            $this->preferences[$key] = $user->wantsNotification($key);
        }
    }

    public function save(): void
    {
        abort_unless(Feature::active(M0Foundations::class), 403);

        $user = auth()->user();
        $stored = $user->notification_preferences ?? [];

        // Only known keys are written, so a tampered payload can't add arbitrary data.
        foreach (array_keys(User::NOTIFICATION_PREFERENCES) as $key) {
            $stored[$key] = (bool) ($this->preferences[$key] ?? true);
        }

        $user->forceFill(['notification_preferences' => $stored])->save();

        $this->saved = true;
    }
};
?>

<div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
    <h3 class="mb-1 text-base font-semibold">Email notifications</h3>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Security emails, such as new sign-ins and password changes, are always sent.</p>

    <form wire:submit="save" class="space-y-4">
        @foreach (\App\Models\User::NOTIFICATION_PREFERENCES as $key => $preference)
            <label class="flex items-start gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model="preferences.{{ $key }}"
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500"
                >
                <span>
                    <span class="block text-sm font-medium">{{ $preference['label'] }}</span>
                    <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $preference['description'] }}</span>
                </span>
            </label>
        @endforeach

        <div class="flex items-center gap-3 pt-1">
            <button
                type="submit"
                class="rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                Save preferences
            </button>
            @if ($saved)
                <span class="text-sm text-green-700 dark:text-green-400">Saved.</span>
            @endif
        </div>
    </form>
</div>
