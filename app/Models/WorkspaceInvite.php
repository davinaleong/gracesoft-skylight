<?php

namespace App\Models;

use Database\Factories\WorkspaceInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['workspace_id', 'email', 'role', 'token_hash', 'invited_by', 'accepted_at', 'expires_at'])]
#[Hidden(['token_hash'])]
class WorkspaceInvite extends Model
{
    /** @use HasFactory<WorkspaceInviteFactory> */
    use HasFactory;

    /** Roles an invite may grant. Workspace ownership is never transferred via invite. */
    public const INVITABLE_ROLES = [Workspace::ROLE_ADMIN, Workspace::ROLE_MEMBER, Workspace::ROLE_VIEWER];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    /**
     * Attach the given user to the invite's workspace with the invited role
     * and mark the invite accepted. Wrapped in a transaction for atomicity.
     */
    public function accept(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->workspace->users()->syncWithoutDetaching([
                $user->id => ['role' => $this->role],
            ]);

            $this->forceFill(['accepted_at' => now()])->save();
        });
    }

    /**
     * Generate a cryptographically-secure token and return both the raw token
     * (shown once, emailed) and the hash (stored in the database).
     *
     * @return array{token: string, hash: string}
     */
    public static function generateToken(): array
    {
        $random = random_bytes(32);
        $token = rtrim(strtr(base64_encode($random), '+/', '-_'), '=');
        $hash = hash('sha256', $token);

        return compact('token', 'hash');
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token_hash', hash('sha256', $token))->first();
    }

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
