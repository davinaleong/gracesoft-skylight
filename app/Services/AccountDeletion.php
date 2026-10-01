<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\MarkdownNote;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

/**
 * Schedules, cancels, and finally purges account deletions. Deletion has a
 * grace period; after it, every row and stored file belonging to the user
 * is removed and their activity log entries are anonymised.
 */
class AccountDeletion
{
    public const GRACE_PERIOD_DAYS = 7;

    /**
     * Reasons the account can't be deleted yet, as user-facing sentences.
     *
     * @return array<int, string>
     */
    public static function blockers(User $user): array
    {
        $blockers = [];

        foreach (self::ownedWorkspaces($user)->get() as $workspace) {
            if ($workspace->users()->whereKeyNot($user->id)->exists()) {
                $blockers[] = "Remove the other members from \"{$workspace->name}\" first. Deleting your account deletes every workspace you own.";
            }

            if ($workspace->plan !== 'free') {
                $blockers[] = "Cancel the paid plan on \"{$workspace->name}\" first.";
            }
        }

        return $blockers;
    }

    public static function schedule(User $user): void
    {
        $user->forceFill(['deletion_scheduled_at' => now()->addDays(self::GRACE_PERIOD_DAYS)])->save();

        ActivityLogger::log('account.deletion_scheduled', null, null, $user->id);
    }

    public static function cancel(User $user): void
    {
        $user->forceFill(['deletion_scheduled_at' => null])->save();

        ActivityLogger::log('account.deletion_cancelled', null, null, $user->id);
    }

    /**
     * Users whose grace period has ended.
     */
    public static function due(): Builder
    {
        return User::query()
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', now());
    }

    /**
     * Permanently remove the user, the workspaces they own, and all stored
     * files. Boards they created in other people's workspaces are handed to
     * that workspace's owner instead of being deleted with them.
     */
    public static function purge(User $user): void
    {
        $ownedWorkspaceIds = self::ownedWorkspaces($user)->pluck('id');
        $ownedBoardIds = Board::query()->whereIn('workspace_id', $ownedWorkspaceIds)->pluck('id');
        $ownedCardIds = Card::query()->whereHas('column', fn (Builder $query) => $query->whereIn('board_id', $ownedBoardIds))->pluck('id');

        $filePaths = self::storedFilePaths($user, $ownedCardIds);
        $disk = config('filesystems.default');

        DB::transaction(function () use ($user, $ownedWorkspaceIds, $ownedBoardIds, $ownedCardIds) {
            // Boards created by the user in workspaces they don't own belong to
            // that workspace's team; reassign them so the user FK cascade
            // doesn't take them down.
            Board::query()
                ->where('user_id', $user->id)
                ->whereNotIn('workspace_id', $ownedWorkspaceIds)
                ->with('workspace')
                ->get()
                ->each(fn (Board $board) => $board->forceFill(['user_id' => $board->workspace->owner_id])->saveQuietly());

            // Activity about the owned (soon deleted) boards and cards goes with them;
            // the user's remaining entries elsewhere are anonymised.
            ActivityLog::query()
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $q) => $q->where('subject_type', (new Board)->getMorphClass())->whereIn('subject_id', $ownedBoardIds))
                    ->orWhere(fn (Builder $q) => $q->where('subject_type', (new Card)->getMorphClass())->whereIn('subject_id', $ownedCardIds)))
                ->delete();

            ActivityLog::query()
                ->where('user_id', $user->id)
                ->update(['user_id' => null, 'ip_hash' => null, 'properties' => null]);

            $user->tokens()->delete();
            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            WorkspaceInvite::query()->where('email', $user->email)->delete();

            // Polymorphic attachments have no FK cascade from their parents, so
            // remove the rows on owned content and on the user's own comments
            // and notes explicitly before the cascade deletes those parents.
            self::attachmentsOnCards($ownedCardIds)->delete();
            self::attachmentsOnOwnContributions($user)->delete();

            Workspace::query()->whereIn('id', $ownedWorkspaceIds)->delete();

            DB::table('features')->where('scope', Feature::serializeScope($user))->delete();

            $user->delete();
        });

        Storage::disk($disk)->delete($filePaths->all());
        Storage::disk('local')->deleteDirectory("exports/{$user->id}");
    }

    private static function ownedWorkspaces(User $user): Builder
    {
        return Workspace::query()->where('owner_id', $user->id);
    }

    /**
     * Every stored file that will be orphaned: the user's own uploads anywhere,
     * every upload on the owned workspaces' cards (whoever uploaded it), and
     * the user's avatar.
     *
     * @param  Collection<int, int>  $ownedCardIds
     * @return Collection<int, string>
     */
    private static function storedFilePaths(User $user, Collection $ownedCardIds): Collection
    {
        return Attachment::query()
            ->whereIn('type', [Attachment::TYPE_IMAGE, Attachment::TYPE_DOCUMENT])
            ->where(fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhereIn('id', self::attachmentsOnCards($ownedCardIds)->select('id'))
                ->orWhereIn('id', self::attachmentsOnOwnContributions($user)->select('id')))
            ->pluck('path')
            ->push($user->avatar_path)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Attachments on the user's own comments and notes, wherever they are.
     */
    private static function attachmentsOnOwnContributions(User $user): Builder
    {
        return Attachment::query()->where(fn (Builder $query) => $query
            ->where(fn (Builder $q) => $q->where('attachable_type', (new Comment)->getMorphClass())
                ->whereIn('attachable_id', Comment::query()->where('user_id', $user->id)->select('id')))
            ->orWhere(fn (Builder $q) => $q->where('attachable_type', (new MarkdownNote)->getMorphClass())
                ->whereIn('attachable_id', MarkdownNote::query()->where('user_id', $user->id)->select('id'))));
    }

    /**
     * Attachments on the given cards or on their checklists, comments, and notes.
     *
     * @param  Collection<int, int>  $cardIds
     */
    private static function attachmentsOnCards(Collection $cardIds): Builder
    {
        return Attachment::query()->where(fn (Builder $query) => $query
            ->where(fn (Builder $q) => $q->where('attachable_type', (new Card)->getMorphClass())->whereIn('attachable_id', $cardIds))
            ->orWhere(fn (Builder $q) => $q->where('attachable_type', (new Checklist)->getMorphClass())
                ->whereIn('attachable_id', Checklist::query()->whereIn('card_id', $cardIds)->select('id')))
            ->orWhere(fn (Builder $q) => $q->where('attachable_type', (new Comment)->getMorphClass())
                ->whereIn('attachable_id', Comment::query()->whereIn('card_id', $cardIds)->select('id')))
            ->orWhere(fn (Builder $q) => $q->where('attachable_type', (new MarkdownNote)->getMorphClass())
                ->whereIn('attachable_id', MarkdownNote::query()->whereIn('card_id', $cardIds)->select('id'))));
    }
}
