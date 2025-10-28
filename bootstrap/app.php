<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
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
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register Spatie Permission middleware
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Apply JSON middleware to API routes
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
