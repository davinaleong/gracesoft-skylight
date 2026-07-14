<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log a discrete activity event.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(
        string $event,
        ?Model $subject = null,
        ?array $properties = null,
        ?int $userId = null,
    ): void {
        $userId ??= auth()->id();

        // A session can outlive the user it belongs to (e.g. the account was
        // deleted from another tab/device). Degrade to an anonymous log entry
        // instead of a hard FK-constraint crash -- the schema already treats
        // user_id as nullable ("system"/unattributed events) for this reason.
        if ($userId !== null && ! User::whereKey($userId)->exists()) {
            $userId = null;
        }

        ActivityLog::create([
            'user_id' => $userId,
            'event' => $event,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_hash' => static::hashIp(Request::ip()),
            'created_at' => now(),
        ]);
    }

    /**
     * Hash an IP address using SHA-256 (never stored raw — privacy-preserving).
     */
    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip) : null;
    }

    /**
     * Build a field-diff properties array for *.updated events.
     *
     * @param  array<string, mixed>  $dirty
     * @param  array<string, mixed>  $original
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $dirty, array $original): array
    {
        $diff = [];
        foreach ($dirty as $field => $newValue) {
            $diff[$field] = ['old' => $original[$field] ?? null, 'new' => $newValue];
        }

        return $diff;
    }
}
