<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBackupAutoTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move backups older than 15 days to trash automatically';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $this->info('Processing auto-trash for old backups...');

        try {
            $result = $service->processAutoTrash();

            $this->info("Found {$result['total']} backup(s) eligible for auto-trash.");
            $this->info("Moved {$result['moved']} backup(s) to trash successfully.");

            Log::info('Backup auto-trash processed', [
                'total_eligible' => $result['total'],
                'moved' => $result['moved'],
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process auto-trash: {$e->getMessage()}");
            Log::error('Backup auto-trash command failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }
}
