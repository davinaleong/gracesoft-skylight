<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Comment;
use App\Models\MarkdownNote;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

/**
 * Builds a full personal data export for one user: their profile, every
 * board in the workspaces they own, and their own comments and notes on
 * boards in workspaces they don't own (without the surrounding board data,
 * which belongs to that workspace's owner).
 */
class AccountExporter
{
    public const CSV_HEADERS = [
        'workspace', 'board', 'column', 'title', 'description', 'color', 'starts_at', 'ends_at', 'labels', 'checklist_progress', 'comments_count',
    ];

    /**
     * Whether the export is big enough to build in a queued job and email.
     */
    public static function shouldQueue(User $user): bool
    {
        return self::ownedCards($user)->count() > config('exports.queue_threshold_cards');
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user): array
    {
        $ownedWorkspaceIds = self::ownedWorkspaces($user)->pluck('id');

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'notification_preferences' => collect(User::NOTIFICATION_PREFERENCES)
                    ->keys()
                    ->mapWithKeys(fn (string $key): array => [$key => $user->wantsNotification($key)])
                    ->all(),
                'api_tokens' => $user->tokens()->get()->map(fn ($token): array => [
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'created_at' => $token->created_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                ])->values()->all(),
            ],
            'workspace_memberships' => $user->workspaces()->get()->map(fn (Workspace $workspace): array => [
                'name' => $workspace->name,
                'role' => $workspace->pivot->role,
            ])->values()->all(),
            'owned_workspaces' => self::ownedWorkspaces($user)->get()->map(fn (Workspace $workspace): array => [
                'name' => $workspace->name,
                'plan' => $workspace->plan,
                'boards' => $workspace->boards()->get()->map(fn (Board $board): array => self::boardToArray($board))->values()->all(),
            ])->values()->all(),
            'contributions_elsewhere' => [
                'comments' => Comment::query()
                    ->where('user_id', $user->id)
                    ->whereHas('card.column.board', fn (Builder $query) => $query->whereNotIn('workspace_id', $ownedWorkspaceIds))
                    ->get()
                    ->map(fn (Comment $comment): array => [
                        'body' => $comment->body,
                        'created_at' => $comment->created_at?->toIso8601String(),
                    ])->values()->all(),
                'notes' => MarkdownNote::query()
                    ->where('user_id', $user->id)
                    ->whereHas('card.column.board', fn (Builder $query) => $query->whereNotIn('workspace_id', $ownedWorkspaceIds))
                    ->get()
                    ->map(fn (MarkdownNote $note): array => [
                        'name' => $note->name,
                        'content' => $note->content,
                        'created_at' => $note->created_at?->toIso8601String(),
                    ])->values()->all(),
            ],
        ];
    }

    /**
     * One row per card on boards in workspaces the user owns.
     *
     * @return array<int, array<string, string>>
     */
    public static function cardRows(User $user): array
    {
        return self::ownedCards($user)
            ->with(['column.board.workspace', 'labels', 'checklists.items', 'comments'])
            ->get()
            ->map(function (Card $card): array {
                $totalItems = $card->checklists->sum(fn ($checklist) => $checklist->items->count());
                $completedItems = $card->checklists->sum(fn ($checklist) => $checklist->items->where('is_completed', true)->count());

                return [
                    'workspace' => $card->column->board->workspace->name,
                    'board' => $card->column->board->name,
                    'column' => $card->column->name,
                    'title' => $card->title,
                    'description' => (string) $card->description,
                    'color' => (string) $card->color,
                    'starts_at' => $card->starts_at?->toDateString() ?? '',
                    'ends_at' => $card->ends_at?->toDateString() ?? '',
                    'labels' => $card->labels->pluck('name')->implode('; '),
                    'checklist_progress' => $totalItems > 0 ? "{$completedItems}/{$totalItems}" : '',
                    'comments_count' => (string) $card->comments->count(),
                ];
            })
            ->all();
    }

    /**
     * Write the export as a zip (skylight-export.json + cards.csv) to an absolute path.
     */
    public static function writeArchive(User $user, string $absolutePath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create export archive at {$absolutePath}.");
        }

        $zip->addFromString('skylight-export.json', json_encode(self::toArray($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('cards.csv', self::toCsv(self::cardRows($user)));
        $zip->close();
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private static function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::CSV_HEADERS);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @return array<string, mixed>
     */
    private static function boardToArray(Board $board): array
    {
        $board->load([
            'labels',
            'columns.cards.labels',
            'columns.cards.checklists.items',
            'columns.cards.checklists.attachments',
            'columns.cards.comments.user',
            'columns.cards.comments.attachments',
            'columns.cards.markdownNotes.user',
            'columns.cards.markdownNotes.attachments',
            'columns.cards.attachments',
        ]);

        return [
            'id' => $board->uuid,
            'name' => $board->name,
            'description' => $board->description,
            'labels' => $board->labels->map(fn ($label): array => ['name' => $label->name, 'color' => $label->color])->values()->all(),
            'columns' => $board->columns->map(fn ($column): array => [
                'name' => $column->name,
                'position' => $column->position,
                'cards' => $column->cards->map(fn (Card $card): array => [
                    'title' => $card->title,
                    'description' => $card->description,
                    'color' => $card->color,
                    'position' => $card->position,
                    'starts_at' => $card->starts_at?->toIso8601String(),
                    'ends_at' => $card->ends_at?->toIso8601String(),
                    'labels' => $card->labels->pluck('name')->values()->all(),
                    'attachments' => self::attachmentsToArray($card->attachments),
                    'checklists' => $card->checklists->map(fn ($checklist): array => [
                        'name' => $checklist->name,
                        'items' => $checklist->items->map(fn ($item): array => [
                            'body' => $item->body,
                            'is_completed' => $item->is_completed,
                        ])->values()->all(),
                        'attachments' => self::attachmentsToArray($checklist->attachments),
                    ])->values()->all(),
                    'comments' => $card->comments->map(fn (Comment $comment): array => [
                        'author' => $comment->user?->name,
                        'body' => $comment->body,
                        'created_at' => $comment->created_at?->toIso8601String(),
                        'attachments' => self::attachmentsToArray($comment->attachments),
                    ])->values()->all(),
                    'notes' => $card->markdownNotes->map(fn (MarkdownNote $note): array => [
                        'author' => $note->user?->name,
                        'name' => $note->name,
                        'content' => $note->content,
                        'attachments' => self::attachmentsToArray($note->attachments),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * Metadata only; stored files are not bundled into the export.
     *
     * @param  Collection<int, Attachment>  $attachments
     * @return array<int, array<string, mixed>>
     */
    private static function attachmentsToArray(Collection $attachments): array
    {
        return $attachments->map(fn (Attachment $attachment): array => [
            'type' => $attachment->type,
            'name' => $attachment->name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'url' => $attachment->isLink() ? $attachment->path : null,
            'created_at' => $attachment->created_at?->toIso8601String(),
        ])->values()->all();
    }

    private static function ownedWorkspaces(User $user): Builder
    {
        return Workspace::query()->where('owner_id', $user->id);
    }

    private static function ownedCards(User $user): Builder
    {
        return Card::query()->whereHas('column.board.workspace', fn (Builder $query) => $query->where('owner_id', $user->id));
    }
}
