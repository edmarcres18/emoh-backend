<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup {--retention=30 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old backup files based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keepDays = (int) $this->option('retention');

        $this->info("Cleaning up backup files older than {$keepDays} days...");

        try {
            $deletedCount = $this->cleanOldBackups($keepDays);

            if ($deletedCount > 0) {
                $this->info("✓ Cleaned {$deletedCount} old backup(s) older than {$keepDays} days");
            } else {
                $this->info("✓ No old backups found to clean up");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clean old backup files.
     */
    private function cleanOldBackups(int $keepDays): int
    {
        $backupPath = $this->getBackupStoragePath();

        if (!File::exists($backupPath)) {
            return 0;
        }

        $files = File::files($backupPath);
        $cutoffTime = now()->subDays($keepDays)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.sql') && $file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get backup storage path.
     */
    private function getBackupStoragePath(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database');
    }
}
