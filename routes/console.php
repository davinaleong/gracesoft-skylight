<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P2: send due-today and overdue card reminders every morning at 08:00
Schedule::command('app:send-card-due-reminders')->dailyAt('08:00');
