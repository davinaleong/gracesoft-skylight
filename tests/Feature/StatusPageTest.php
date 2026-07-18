<?php

use App\Services\SystemStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('SystemStatusService', function () {
    it('reports all checks healthy under normal conditions', function () {
        $status = new SystemStatusService;

        expect($status->isHealthy())->toBeTrue();

        $names = collect($status->checks())->pluck('name');
        expect($names)->toContain('Database', 'Cache', 'File storage', 'Background jobs');
    });

    it('reports the background-jobs check unhealthy when a job failed recently', function () {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Something broke',
            'failed_at' => now(),
        ]);

        $status = new SystemStatusService;
        $jobsCheck = collect($status->checks())->firstWhere('name', 'Background jobs');

        expect($jobsCheck['healthy'])->toBeFalse();
        expect($status->isHealthy())->toBeFalse();
    });

    it('reports file storage unhealthy when the configured disk cannot be resolved', function () {
        config(['filesystems.default' => 'nonexistent-disk']);

        $status = new SystemStatusService;
        $storageCheck = collect($status->checks())->firstWhere('name', 'File storage');

        expect($storageCheck['healthy'])->toBeFalse();
    });
});

describe('status page', function () {
    it('shows all systems operational when healthy', function () {
        $this->get(route('status'))
            ->assertOk()
            ->assertSee('All systems operational')
            ->assertSee('Database')
            ->assertSee('Cache')
            ->assertSee('File storage')
            ->assertSee('Background jobs');
    });

    it('shows a degraded message when a check is unhealthy', function () {
        config(['filesystems.default' => 'nonexistent-disk']);

        $this->get(route('status'))
            ->assertOk()
            ->assertSee('Some systems are experiencing issues');
    });

    it('is reachable without authentication', function () {
        $this->get(route('status'))->assertOk();
    });
});
