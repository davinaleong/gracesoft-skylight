<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public bool $open = false;

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->take(15)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }
};
?>

<div class="relative" x-data @click.outside="$wire.set('open', false)" wire:poll.30s="$refresh">
    <button
        wire:click="$toggle('open')"
        class="relative rounded-lg p-1.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        aria-label="Notifications"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($this->unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] rounded-xl bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-200 dark:ring-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-4 py-3">
                <span class="text-sm font-semibold">Notifications</span>
                @if ($this->unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        Mark all as read
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse ($this->notifications as $notification)
                    <a
                        href="{{ $notification->data['url'] ?? '#' }}"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="block border-b border-gray-50 dark:border-gray-800/60 px-4 py-3 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors {{ $notification->read_at ? '' : 'bg-indigo-50/50 dark:bg-indigo-900/10' }}"
                    >
                        <div class="flex items-start gap-2">
                            @unless ($notification->read_at)
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>
                            @endunless
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                @if (!empty($notification->data['body']))
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $notification->data['body'] }}</p>
                                @endif
                                <p class="mt-1 text-[11px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
