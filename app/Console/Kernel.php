<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily database backup at midnight (runs synchronously)
        $schedule->command('backup:database')
                 ->dailyAt('00:00')
                 ->name('daily-database-backup')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     \Log::info('Daily database backup completed successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('Daily database backup failed');
                 });

        // Daily auto-trash old backups (older than 15 days) at 1:00 AM
        $schedule->command('backup:auto-trash')
                 ->dailyAt('01:00')
                 ->name('backup-auto-trash')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     \Log::info('Backup auto-trash processed successfully');
                 });

        // Daily permanent deletion of old trash items (older than 7 days) at 2:00 AM
        $schedule->command('backup:cleanup-trash')
                 ->dailyAt('02:00')
                 ->name('backup-cleanup-trash')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     \Log::info('Backup trash cleanup processed successfully');
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
