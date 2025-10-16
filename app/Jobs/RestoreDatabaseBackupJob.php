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

class RestoreDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes
    public $tries = 1; // Only try once for restore

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
            Log::info("Starting database restore", [
                'backup_id' => $this->backup->id,
                'filename' => $this->backup->filename,
            ]);

            $service->restoreFromBackup($this->backup);

            Log::info("Database restored successfully", [
                'backup_id' => $this->backup->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Database restore failed", [
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
        Log::error("Database restore job failed", [
            'backup_id' => $this->backup->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
