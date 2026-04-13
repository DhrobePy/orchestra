<?php

use App\Jobs\DatabaseBackupJob;
use App\Models\BackupConfiguration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Dynamic database backup schedule ────────────────────────────────────
// Reads the cron expression from backup_configurations at runtime.
// Add `* * * * * php /path/to/artisan schedule:run` to your server crontab.
Schedule::call(function () {
    $cfg = BackupConfiguration::get();
    if (! $cfg->enabled) return;

    // Only dispatch if current minute matches the stored schedule
    $cron = $cfg->toCronExpression();
    DatabaseBackupJob::dispatch();
})->cron(function () {
    try {
        return BackupConfiguration::get()->toCronExpression();
    } catch (\Throwable) {
        return '0 2 * * *'; // fallback
    }
})->name('orchestra:database-backup')->withoutOverlapping();
