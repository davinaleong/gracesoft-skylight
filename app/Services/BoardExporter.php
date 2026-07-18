<?php

namespace App\Services;

use App\Models\Board;

class BoardExporter
{
    /**
     * Full nested structure: board -> columns -> cards -> labels/checklists/comments.
     *
     * @return array<string, mixed>
     */
    public static function toArray(Board $board): array
    {
        $board->loadMissing([
            'columns.cards.labels',
            'columns.cards.checklists.items',
            'columns.cards.comments.user',
        ]);

        return [
            'board' => [
                'id' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
            ],
            'columns' => $board->columns->map(fn ($column) => [
                'id' => $column->id,
                'name' => $column->name,
                'position' => $column->position,
                'cards' => $column->cards->map(fn ($card) => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'color' => $card->color,
                    'position' => $card->position,
                    'starts_at' => $card->starts_at?->toIso8601String(),
                    'ends_at' => $card->ends_at?->toIso8601String(),
                    'labels' => $card->labels->map(fn ($label) => [
                        'name' => $label->name,
                        'color' => $label->color,
                    ])->values()->all(),
                    'checklists' => $card->checklists->map(fn ($checklist) => [
                        'name' => $checklist->name,
                        'items' => $checklist->items->map(fn ($item) => [
                            'body' => $item->body,
                            'is_completed' => $item->is_completed,
                        ])->values()->all(),
                    ])->values()->all(),
                    'comments' => $card->comments->map(fn ($comment) => [
                        'author' => $comment->user->name,
                        'body' => $comment->body,
                        'created_at' => $comment->created_at->toIso8601String(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public static function toJson(Board $board): string
    {
        return json_encode(self::toArray($board), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * One row per card, flattened for spreadsheet use.
     *
     * @return array<int, array<string, string>>
     */
    public static function toCsvRows(Board $board): array
    {
        $board->loadMissing(['columns.cards.labels', 'columns.cards.checklists.items', 'columns.cards.comments']);

        $rows = [];

        foreach ($board->columns as $column) {
            foreach ($column->cards as $card) {
                $totalItems = $card->checklists->sum(fn ($checklist) => $checklist->items->count());
                $completedItems = $card->checklists->sum(fn ($checklist) => $checklist->items->where('is_completed', true)->count());

                $rows[] = [
                    'column' => $column->name,
                    'title' => $card->title,
                    'description' => (string) $card->description,
                    'color' => (string) $card->color,
                    'starts_at' => $card->starts_at?->toDateString() ?? '',
                    'ends_at' => $card->ends_at?->toDateString() ?? '',
                    'labels' => $card->labels->pluck('name')->implode('; '),
                    'checklist_progress' => $totalItems > 0 ? "{$completedItems}/{$totalItems}" : '',
                    'comments_count' => (string) $card->comments->count(),
                ];
            }
        }

        return $rows;
    }

    public const CSV_HEADERS = [
        'column', 'title', 'description', 'color', 'starts_at', 'ends_at', 'labels', 'checklist_progress', 'comments_count',
    ];
}
