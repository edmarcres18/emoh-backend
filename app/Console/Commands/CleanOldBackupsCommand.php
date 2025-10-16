<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:clean
                            {--trash-days=15 : Number of days before moving backups to trash}
                            {--delete-days=7 : Number of days in trash before permanent deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old database backups: Move backups older than 15 days to trash, and permanently delete trash older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $trashDays = (int) $this->option('trash-days');
        $deleteDays = (int) $this->option('delete-days');

        $this->info('Starting backup cleanup process...');
        $this->newLine();

        // Step 1: Move old active backups to trash
        $this->info("Step 1: Moving backups older than {$trashDays} days to trash...");
        $backupsToTrash = DatabaseBackup::active()
            ->olderThan($trashDays)
            ->get();

        $trashedCount = 0;
        foreach ($backupsToTrash as $backup) {
            $backup->update(['trashed_at' => now()]);
            $trashedCount++;
            $this->line("  ✓ Moved to trash: {$backup->filename}");
        }

        if ($trashedCount > 0) {
            $this->info("  → {$trashedCount} backup(s) moved to trash");
        } else {
            $this->comment('  → No backups to move to trash');
        }

        $this->newLine();

        // Step 2: Permanently delete trashed backups older than specified days
        $this->info("Step 2: Permanently deleting trashed backups older than {$deleteDays} days...");
        $backupsToDelete = DatabaseBackup::trashed()
            ->where('trashed_at', '<=', now()->subDays($deleteDays))
            ->get();

        $deletedCount = 0;
        $deletedSize = 0;
        foreach ($backupsToDelete as $backup) {
            // Delete physical file
            $filePath = storage_path('app/' . $backup->path);
            if (file_exists($filePath)) {
                $deletedSize += filesize($filePath);
                unlink($filePath);
                $this->line("  ✓ Deleted file: {$backup->filename}");
            } else {
                $this->warn("  ⚠ File not found (will remove record): {$backup->filename}");
            }

            // Delete database record
            $backup->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $this->info("  → {$deletedCount} backup(s) permanently deleted");
            $this->info("  → Freed up space: " . $this->formatBytes($deletedSize));
        } else {
            $this->comment('  → No trashed backups to delete');
        }

        $this->newLine();

        // Summary
        $this->info('Cleanup Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Moved to trash', $trashedCount],
                ['Permanently deleted', $deletedCount],
            ]
        );

        // Show current stats
        $activeCount = DatabaseBackup::active()->count();
        $trashedCountTotal = DatabaseBackup::trashed()->count();
        $totalSize = DatabaseBackup::active()->sum('size');

        $this->newLine();
        $this->info('Current Backup Statistics:');
        $this->table(
            ['Status', 'Count', 'Size'],
            [
                ['Active Backups', $activeCount, $this->formatBytes($totalSize)],
                ['Trashed Backups', $trashedCountTotal, '-'],
            ]
        );

        $this->newLine();
        $this->info('Backup cleanup completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $pow = floor($base);

        return round(pow(1024, $base - $pow), $precision) . ' ' . $units[$pow];
    }
}
