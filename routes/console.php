<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P2: send due-today and overdue card reminders every morning at 08:00
Schedule::command('app:send-card-due-reminders')->dailyAt('08:00');

// Milestone 8: nightly backup (database + storage/app + .env), then prune
// per config/backup.php's retention strategy, then verify what's left is
// actually healthy (age/size) -- three separate steps so a cleanup or
// monitor failure is distinguishable from a backup failure in the logs.
Schedule::command('backup:run')->dailyAt('01:00')->onOneServer();
Schedule::command('backup:clean')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('02:00')->onOneServer();
