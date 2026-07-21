<?php

use App\Notifications\FeedbackReceivedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }
    }

    public function send(): void
    {
        // Route-level `throttle:` middleware only protects the initial page
        // load -- Livewire's AJAX action calls go through their own internal
        // endpoint, not this page's route, so a mutating action that sends
        // email needs its own rate check here.
        $limiterKey = 'feedback:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->addError('message', 'Too many submissions — please try again in a few minutes.');

            return;
        }

        RateLimiter::hit($limiterKey, 60);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Notification::route('mail', config('mail.feedback_address'))
            ->notify(new FeedbackReceivedNotification($this->name, $this->email, $this->message));

        $this->sent = true;
        $this->reset('message');
    }
};
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-semibold">Feedback</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Found a bug, have a feature request, or just want to tell us something? This goes straight to the team.
    </p>

    @if ($sent)
        <div class="mt-6 rounded-xl bg-green-50 dark:bg-green-900/20 p-5 ring-1 ring-green-200 dark:ring-green-900/40">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">Thanks — your feedback was sent.</p>
            <button wire:click="$set('sent', false)" class="mt-2 text-xs font-medium text-green-700 dark:text-green-400 hover:underline">Send more feedback</button>
        </div>
    @else
        <form wire:submit="send" class="mt-6 space-y-4 rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
            <div>
                <label for="name" class="block text-sm font-medium mb-1.5">Name</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                >
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-1.5">Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="feedback-message" class="block text-sm font-medium mb-1.5">Message</label>
                <textarea
                    id="feedback-message"
                    wire:model="message"
                    rows="5"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('message') border-red-500 @enderror"
                ></textarea>
                @error('message')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="send"
                class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="send">Send feedback</span>
                <span wire:loading wire:target="send">Sending…</span>
            </button>
        </form>
    @endif
</div>
