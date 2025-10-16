<?php

namespace App\Jobs;

use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DatabaseBackup $backup
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DatabaseBackupService $service): void
    {
        try {
            Log::info("Starting database backup creation", [
                'backup_id' => $this->backup->id,
                'filename' => $this->backup->filename,
            ]);

            $service->executeBackup($this->backup);

            Log::info("Database backup created successfully", [
                'backup_id' => $this->backup->id,
                'file_size' => $this->backup->file_size,
            ]);

        } catch (\Exception $e) {
            Log::error("Database backup failed", [
                'backup_id' => $this->backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Database backup job failed permanently", [
            'backup_id' => $this->backup->id,
            'error' => $exception->getMessage(),
        ]);

        $this->backup->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
