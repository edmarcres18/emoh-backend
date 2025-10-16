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
        // Daily scheduled database backup at 2:00 AM
        $schedule->command('backups:create-scheduled')
                 ->dailyAt('02:00')
                 ->name('daily-database-backup')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Daily cleanup: Move backups older than 15 days to trash, delete trash older than 7 days
        $schedule->command('backups:clean --trash-days=15 --delete-days=7')
                 ->dailyAt('03:00')
                 ->name('daily-backup-cleanup')
                 ->withoutOverlapping()
                 ->runInBackground();
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
