<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['card_id', 'user_id', 'type', 'path', 'name', 'mime_type', 'size'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    /** Attachment types */
    public const TYPE_IMAGE = 'image';

    public const TYPE_LINK = 'link';

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function isLink(): bool
    {
        return $this->type === self::TYPE_LINK;
    }

    /**
     * Generate a short-lived temporary URL for image attachments (~5 min).
     * Falls back to a plain URL for disks that don't support temporary URLs.
     */
    public function temporaryUrl(int $expiryMinutes = 5): string
    {
        $disk = Storage::disk(config('filesystems.default'));

        if (method_exists($disk->getAdapter(), 'temporaryUrl')) {
            return $disk->temporaryUrl($this->path, now()->addMinutes($expiryMinutes));
        }

        return $disk->url($this->path);
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}
