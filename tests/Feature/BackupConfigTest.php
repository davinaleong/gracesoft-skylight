<?php

use Illuminate\Support\Facades\Artisan;

it('backs up the default database connection', function () {
    expect(config('backup.backup.source.databases'))->toContain(config('database.default'));
});

it('backs up user-uploaded files and env config, not the whole codebase', function () {
    $include = config('backup.backup.source.files.include');

    expect($include)->toContain(storage_path('app'));
    expect($include)->toContain(base_path('.env'));
    expect($include)->not->toContain(base_path());
});

it('writes to both the local disk and a dedicated off-site backups disk', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['local', 'backups']);
});

it('continues writing to other disks if one destination fails', function () {
    expect(config('backup.backup.destination.continue_on_failure'))->toBeTrue();
});

it('configures the backups disk as a distinct S3 destination, separate from the primary attachments disk', function () {
    expect(config('filesystems.disks.backups.driver'))->toBe('s3');
    expect(config('filesystems.disks.backups'))->not->toBe(config('filesystems.disks.s3'));
});

it('schedules nightly backup, cleanup, and health-monitoring commands', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($output)->toContain('backup:run');
    expect($output)->toContain('backup:clean');
    expect($output)->toContain('backup:monitor');
});
