<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['board_share_link_id', 'ip_hash', 'user_agent', 'accessed_at'])]
class ShareLinkAccess extends Model
{
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(BoardShareLink::class, 'board_share_link_id');
    }

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }
}
