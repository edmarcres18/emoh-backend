<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CreateScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:create-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a scheduled database backup automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating scheduled database backup...');

        try {
            // Get database configuration
            $database = Config::get('database.connections.' . Config::get('database.default'));
            $dbName = $database['database'];
            $dbUser = $database['username'];
            $dbPassword = $database['password'];
            $dbHost = $database['host'];
            $dbPort = $database['port'] ?? 3306;

            // Create backup filename with timestamp
            $timestamp = now()->format('Y-m-d_His');
            $filename = "scheduled_backup_{$dbName}_{$timestamp}.sql";
            
            // Create backups directory if it doesn't exist
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $backupPath = $backupDir . '/' . $filename;

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s > %s 2>&1',
                escapeshellarg($dbUser),
                escapeshellarg($dbPassword),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            $this->line('Executing backup command...');

            // Execute backup command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($backupPath)) {
                throw new \Exception('Backup creation failed: ' . implode("\n", $output));
            }

            // Get file size
            $fileSize = filesize($backupPath);

            // Get the first System Admin user as the creator
            $systemAdmin = User::role('System Admin')->first();
            
            if (!$systemAdmin) {
                throw new \Exception('No System Admin user found to assign as backup creator');
            }

            // Save backup record to database
            $backup = DatabaseBackup::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => $fileSize,
                'created_by' => $systemAdmin->id,
            ]);

            $this->info('✓ Backup created successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Filename', $backup->filename],
                    ['Size', $this->formatBytes($fileSize)],
                    ['Created by', $systemAdmin->name],
                    ['Created at', $backup->created_at->format('Y-m-d H:i:s')],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create backup: ' . $e->getMessage());
            
            // Clean up partial backup file if it exists
            if (isset($backupPath) && file_exists($backupPath)) {
                unlink($backupPath);
                $this->line('Cleaned up partial backup file');
            }

            return Command::FAILURE;
        }
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
