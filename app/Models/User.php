<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'oauth_provider', 'oauth_provider_id', 'avatar_path', 'onboarding_dismissed_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class)->orderBy('position');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Every user has exactly one workspace today (their personal one, created
     * on signup). This is the single seam multi-workspace support will widen later.
     */
    public function currentWorkspace(): ?Workspace
    {
        return $this->workspaces()->orderBy('workspace_user.created_at')->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'onboarding_dismissed_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Check whether the user has a specific P2 notification preference enabled.
     * Defaults to true if the preference has not been explicitly set.
     */
    public function wantsNotification(string $key): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs[$key] ?? true);
    }

    /**
     * Generate a URL for the user's avatar, if one is set.
     * Mirrors Attachment::temporaryUrl()'s disk-agnostic delivery approach.
     */
    public function avatarUrl(int $expiryMinutes = 60): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        $disk = Storage::disk(config('filesystems.default'));

        if (method_exists($disk->getAdapter(), 'temporaryUrl')) {
            return $disk->temporaryUrl($this->avatar_path, now()->addMinutes($expiryMinutes));
        }

        return $disk->url($this->avatar_path);
    }

    /**
     * The @handle used for mentions in comments -- the user's name, slugged
     * with no separator (e.g. "Ada Lovelace" -> "adalovelace").
     */
    public function mentionHandle(): string
    {
        return Str::slug($this->name, '');
    }
}
