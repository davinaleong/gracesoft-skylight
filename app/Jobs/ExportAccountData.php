<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Account\AccountExportReadyNotification;
use App\Services\AccountExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportAccountData implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $filename = Str::uuid()->toString().'.zip';
        $relativePath = "exports/{$this->user->id}/{$filename}";
        $disk = Storage::disk('local');

        $disk->makeDirectory("exports/{$this->user->id}");

        AccountExporter::writeArchive($this->user, $disk->path($relativePath));

        $this->user->notify(new AccountExportReadyNotification($filename));
    }
}
