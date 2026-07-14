<?php

use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Board $board;

    public function mount(Board $board): void
    {
        $this->board = $board;
    }

    #[Computed]
    public function activities()
    {
        return ActivityLog::query()
            ->where(function ($query) {
                $query->where('subject_type', Board::class)->where('subject_id', $this->board->id);
            })
            ->orWhere(function ($query) {
                $query->where('subject_type', Card::class)->where('properties->board_id', $this->board->id);
            })
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get();
    }

    public function describe(ActivityLog $log): string
    {
        $props = $log->properties ?? [];
        $actor = $log->user?->name ?? 'Someone';

        return match ($log->event) {
            'board.created' => "{$actor} created this board",
            'board.updated' => "{$actor} updated the board (".implode(', ', array_keys($props)).')',
            'card.created' => "{$actor} created card \"".($props['card_title'] ?? '?').'"',
            'card.updated' => "{$actor} updated card \"".($props['card_title'] ?? '?').'" ('.implode(', ', array_keys(array_diff_key($props, array_flip(['card_title', 'board_id'])))).')',
            'card.moved' => "{$actor} moved card \"".($props['card_title'] ?? '?').'" to '.$this->columnName($props['to_column_id'] ?? null),
            'card.deleted' => "{$actor} deleted card \"".($props['card_title'] ?? '?').'"',
            'share_link.created' => "{$actor} created a share link",
            'share_link.revoked' => "{$actor} revoked a share link",
            'share_link.accessed' => 'A share link was accessed',
            default => "{$actor} did something ({$log->event})",
        };
    }

    protected function columnName(?int $columnId): string
    {
        if (! $columnId) {
            return 'another column';
        }

        return Column::find($columnId)?->name ?? 'a deleted column';
    }
};
?>

<div class="space-y-3">
    @forelse ($this->activities as $log)
        <div class="flex items-start gap-2.5 text-sm">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600"></span>
            <div class="min-w-0">
                <p class="text-gray-700 dark:text-gray-300">{{ $this->describe($log) }}</p>
                <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">No activity yet.</p>
    @endforelse
</div>
