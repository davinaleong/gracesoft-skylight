<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:prune-account-exports')]
#[Description('Delete queued account export archives once their download link has expired')]
class PruneAccountExports extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(config('exports.link_lifetime_hours'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->allFiles('exports') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} expired export archive(s).");

        return self::SUCCESS;
    }
}
