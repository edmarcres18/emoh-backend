<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create {--type=manual : Type of backup (manual|scheduled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database backup';

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
        $type = $this->option('type');

        if (!in_array($type, ['manual', 'scheduled'])) {
            $this->error('Invalid type. Use "manual" or "scheduled".');
            return 1;
        }

        $this->info("Creating {$type} database backup...");

        try {
            $backup = $this->backupService->createBackup($type);

            $this->info("Backup created successfully!");
            $this->info("Filename: {$backup->filename}");
            $this->info("Status: {$backup->status}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }
    }
}
