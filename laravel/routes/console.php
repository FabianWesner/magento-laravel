<?php

use App\Modernization\Modules\ModuleRegistry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('modernization:modules', function (ModuleRegistry $registry): int {
    foreach ($registry->enabled() as $manifest) {
        $this->line("{$manifest->id} | {$manifest->name} | {$manifest->version}");
    }

    foreach ($registry->errors() as $error) {
        $this->error($error);
    }

    return $registry->healthy() ? self::SUCCESS : self::FAILURE;
})->purpose('List enabled Laravel modernization modules');

Schedule::command('modernization:cron-status --dispatch')
    ->name('modernization-cron-diagnostics-status')
    ->everyFiveMinutes()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/modernization-cron-status.log'));
