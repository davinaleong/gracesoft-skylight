<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function steps(): array
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspace();

        return [
            [
                'label' => 'Create your first board',
                'done' => $user->boards()->count() > 1,
                'cta' => 'Boards are just below',
                'url' => route('home'),
            ],
            [
                'label' => 'Invite a teammate',
                'done' => $workspace && $workspace->users()->count() > 1,
                'cta' => 'Invite',
                'url' => route('team'),
            ],
            [
                'label' => 'Add a profile photo',
                'done' => (bool) $user->avatar_path,
                'cta' => 'Update profile',
                'url' => route('profile'),
            ],
        ];
    }

    #[Computed]
    public function isComplete(): bool
    {
        return collect($this->steps)->every(fn ($step) => $step['done']);
    }

    #[Computed]
    public function shouldShow(): bool
    {
        return ! auth()->user()->onboarding_dismissed_at && ! $this->isComplete;
    }

    public function dismiss(): void
    {
        auth()->user()->update(['onboarding_dismissed_at' => now()]);
    }
};
?>

<div>
@if ($this->shouldShow)
    @php $doneCount = collect($this->steps)->where('done', true)->count(); @endphp
    <div class="mb-6 rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-sm font-semibold">Get started</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $doneCount }} of {{ count($this->steps) }} complete</p>
            </div>
            <button wire:click="dismiss" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Dismiss">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="mb-3 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800">
            <div class="h-1.5 rounded-full bg-indigo-500 transition-all" style="width: {{ count($this->steps) > 0 ? ($doneCount / count($this->steps) * 100) : 0 }}%"></div>
        </div>

        <ul class="space-y-2">
            @foreach ($this->steps as $step)
                <li class="flex items-center justify-between gap-3 text-sm">
                    <div class="flex items-center gap-2.5">
                        @if ($step['done'])
                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                        @else
                            <span class="h-4 w-4 shrink-0 rounded-full border-2 border-gray-300 dark:border-gray-600"></span>
                        @endif
                        <span @class(['text-gray-400 line-through' => $step['done'], 'text-gray-700 dark:text-gray-300' => ! $step['done']])>
                            {{ $step['label'] }}
                        </span>
                    </div>
                    @unless ($step['done'])
                        <a href="{{ $step['url'] }}" class="shrink-0 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ $step['cta'] }}
                        </a>
                    @endunless
                </li>
            @endforeach
        </ul>
    </div>
@endif
</div>
