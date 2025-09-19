<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup {--days=30 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old database backups';

    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be at least 1.');
            return 1;
        }

        $this->info("Cleaning up backups older than {$days} days...");

        try {
            $deletedCount = $this->backupService->cleanupOldBackups($days);

            $this->info("Cleanup completed successfully!");
            $this->info("Deleted {$deletedCount} old backups.");

            return 0;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            return 1;
        }
    }
}
