<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Bluehost shared hosting: cron every minute → `php artisan schedule:run`
| Processes one batch of queued jobs without a long-running worker.
*/
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('events:send-reminders')
    ->hourly()
    ->withoutOverlapping();
