<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemStatusService
{
    /**
     * @return array<int, array{name: string, healthy: bool, detail: string}>
     */
    public function checks(): array
    {
        return [
            $this->database(),
            $this->cache(),
            $this->storage(),
            $this->queue(),
        ];
    }

    public function isHealthy(): bool
    {
        return collect($this->checks())->every(fn ($check) => $check['healthy']);
    }

    /**
     * @return array{name: string, healthy: bool, detail: string}
     */
    private function database(): array
    {
        try {
            DB::connection()->getPdo();

            return ['name' => 'Database', 'healthy' => true, 'detail' => 'Connected'];
        } catch (Throwable $e) {
            return ['name' => 'Database', 'healthy' => false, 'detail' => 'Unreachable'];
        }
    }

    /**
     * @return array{name: string, healthy: bool, detail: string}
     */
    private function cache(): array
    {
        try {
            $key = 'status-check-'.Str::random(8);
            Cache::put($key, true, 5);
            $ok = Cache::pull($key) === true;

            return ['name' => 'Cache', 'healthy' => $ok, 'detail' => $ok ? 'Read/write OK' : 'Read/write failed'];
        } catch (Throwable $e) {
            return ['name' => 'Cache', 'healthy' => false, 'detail' => 'Unreachable'];
        }
    }

    /**
     * @return array{name: string, healthy: bool, detail: string}
     */
    private function storage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default'));
            $path = 'status-check-'.Str::random(8).'.txt';
            $disk->put($path, 'ok');
            $ok = $disk->get($path) === 'ok';
            $disk->delete($path);

            return ['name' => 'File storage', 'healthy' => $ok, 'detail' => $ok ? 'Read/write OK' : 'Read/write failed'];
        } catch (Throwable $e) {
            return ['name' => 'File storage', 'healthy' => false, 'detail' => 'Unreachable'];
        }
    }

    /**
     * @return array{name: string, healthy: bool, detail: string}
     */
    private function queue(): array
    {
        try {
            $failed = DB::table(config('queue.failed.table', 'failed_jobs'))
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            $healthy = $failed === 0;

            return [
                'name' => 'Background jobs',
                'healthy' => $healthy,
                'detail' => $healthy ? 'No failures in the last 24h' : "{$failed} job(s) failed in the last 24h",
            ];
        } catch (Throwable $e) {
            return ['name' => 'Background jobs', 'healthy' => false, 'detail' => 'Unable to check'];
        }
    }
}
