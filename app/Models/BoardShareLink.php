<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['board_id', 'token_hash', 'can_see_comments', 'can_see_attachments', 'revoked_at', 'expires_at'])]
#[Hidden(['token_hash'])]
class BoardShareLink extends Model
{
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(ShareLinkAccess::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Generate a cryptographically-secure token and return both the raw token
     * (shown once) and the hash (stored in the database).
     *
     * @return array{token: string, hash: string}
     */
    public static function generateToken(): array
    {
        // 32 bytes = 256 bits of entropy, base62-encoded
        $random = random_bytes(32);
        $token = rtrim(strtr(base64_encode($random), '+/', '-_'), '=');
        $hash = hash('sha256', $token);

        return compact('token', 'hash');
    }

    /**
     * Find an active (not revoked, not expired) share link by its raw token.
     */
    public static function findByToken(string $token): ?self
    {
        $link = static::where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        return $link && ! $link->isExpired() ? $link : null;
    }

    protected function casts(): array
    {
        return [
            'can_see_comments' => 'boolean',
            'can_see_attachments' => 'boolean',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
