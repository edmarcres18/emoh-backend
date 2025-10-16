<?php

namespace App\Console\Commands;

use App\Jobs\CreateDatabaseBackupJob;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database 
                            {--queue : Queue the backup job instead of running synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $this->info('Starting database backup...');

        try {
            // Create backup record
            $backup = $service->createBackup();

            if ($this->option('queue')) {
                // Queue the backup job
                CreateDatabaseBackupJob::dispatch($backup);
                $this->info("Backup job queued successfully. ID: {$backup->id}");
                Log::info('Database backup job queued', ['backup_id' => $backup->id]);
            } else {
                // Run backup synchronously
                $this->info('Creating backup synchronously...');
                $service->executeBackup($backup);
                $this->info("Backup created successfully!");
                $this->info("Filename: {$backup->filename}");
                $this->info("File size: {$backup->formatted_file_size}");
                Log::info('Database backup created successfully', [
                    'backup_id' => $backup->id,
                    'file_size' => $backup->file_size,
                ]);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to create backup: {$e->getMessage()}");
            Log::error('Database backup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
