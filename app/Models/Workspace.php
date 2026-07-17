<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

#[Fillable(['owner_id', 'name', 'plan'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use Billable, HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    public const ROLE_VIEWER = 'viewer';

    /**
     * Auto-generate a UUID whenever a workspace is first created.
     * The integer PK (id) remains for SQL joins and foreign keys.
     */
    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace) {
            $workspace->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Use the UUID as the route binding key so workspace IDs never appear in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Create a personal workspace for a newly registered user and make them its owner.
     */
    public static function createForUser(User $user, ?string $name = null): self
    {
        return DB::transaction(function () use ($user, $name) {
            $workspace = self::create([
                'owner_id' => $user->id,
                'name' => $name ?? $user->name."'s Workspace",
            ]);

            $workspace->users()->attach($user->id, ['role' => self::ROLE_OWNER]);

            return $workspace;
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasMember(User $user): bool
    {
        return $this->users()->whereKey($user->id)->exists();
    }

    /**
     * The member's role in this workspace, or null if they aren't a member.
     */
    public function roleOf(User $user): ?string
    {
        return $this->users()->whereKey($user->id)->first()?->pivot->role;
    }

    public function isOwner(User $user): bool
    {
        return $this->roleOf($user) === self::ROLE_OWNER;
    }

    public function isViewer(User $user): bool
    {
        return $this->roleOf($user) === self::ROLE_VIEWER;
    }

    /**
     * Owners and admins can invite/remove members and change roles.
     */
    public function canManageMembers(User $user): bool
    {
        return in_array($this->roleOf($user), [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    /**
     * Owners, admins, and members can create/edit/delete boards, columns, cards,
     * and everything under them. Viewers are read-only.
     */
    public function canEditContent(User $user): bool
    {
        return $this->hasMember($user) && ! $this->isViewer($user);
    }

    /**
     * Whether $actor may change $target's role or remove them from the workspace.
     * The owner role is permanent (never assignable via invite, never revocable) so
     * there's always exactly one owner, and nobody — including the owner — can
     * touch the owner's own membership through this path.
     */
    public function canChangeMember(User $actor, User $target): bool
    {
        if ($this->isOwner($target)) {
            return false;
        }

        return $this->canManageMembers($actor);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(WorkspaceInvite::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Cashier bills by "stripeEmail()" -- a workspace has no email of its
     * own, so invoices/receipts go to the owner's address.
     */
    public function stripeEmail(): ?string
    {
        return $this->owner?->email;
    }

    /**
     * @return array{name: string, price_monthly: int, board_limit: ?int, member_limit: ?int, stripe_price_id: ?string}
     */
    public function planLimits(): array
    {
        return config('plans.'.$this->plan) ?? config('plans.free');
    }
}
