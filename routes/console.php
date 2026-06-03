<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('investment_scoring.scheduled_run_enabled', true)) {
    Schedule::command('scoring:run', [
        '--universe' => config('investment_scoring.scheduled_universe', 'ALL'),
    ])
        ->dailyAt(config('investment_scoring.scheduled_run_time', '06:00'))
        ->withoutOverlapping()
        ->runInBackground();
}
