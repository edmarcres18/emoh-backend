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
        // Daily database backup at 2:00 AM
        $schedule->command('backup:database --keep-days=30')
                 ->dailyAt('02:00')
                 ->name('daily-database-backup')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Weekly cleanup of old backups (every Sunday at 3:00 AM)
        $schedule->command('backup:cleanup --retention=30')
                 ->weeklyOn(0, '03:00')
                 ->name('weekly-backup-cleanup')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Monthly deep cleanup (keep only 90 days, every 1st of month at 4:00 AM)
        $schedule->command('backup:cleanup --retention=90')
                 ->monthlyOn(1, '04:00')
                 ->name('monthly-backup-cleanup')
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
