<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBackupPermanentDeletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete backups that have been in trash for more than 7 days';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $this->info('Processing permanent deletion of old trash items...');

        try {
            $result = $service->processPermanentDeletion();

            $this->info("Found {$result['total']} trash item(s) eligible for permanent deletion.");
            $this->info("Deleted {$result['deleted']} backup(s) permanently.");

            Log::info('Backup permanent deletion processed', [
                'total_eligible' => $result['total'],
                'deleted' => $result['deleted'],
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process permanent deletion: {$e->getMessage()}");
            Log::error('Backup permanent deletion command failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }
}
