<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['owner_id', 'name'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

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
}
