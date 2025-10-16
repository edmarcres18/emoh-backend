<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseBackupService
{
    /**
     * Get paginated backups with filters.
     */
    public function getPaginatedBackups(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = DatabaseBackup::with('creator:id,name,email');

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhere('unique_identifier', 'like', "%{$search}%")
                  ->orWhereHas('creator', function (Builder $creatorQuery) use ($search) {
                      $creatorQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply view mode filter (active or trash)
        if (isset($filters['view']) && $filters['view'] === 'trash') {
            $query->trashed();
        } else {
            $query->where('status', '!=', 'in_trash');
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->where('backup_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('backup_date', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'latest';
        switch ($sortBy) {
            case 'latest':
                $query->orderBy('backup_date', 'desc');
                break;
            case 'oldest':
                $query->orderBy('backup_date', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('filename', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('filename', 'desc');
                break;
            case 'size_asc':
                $query->orderBy('file_size', 'asc');
                break;
            case 'size_desc':
                $query->orderBy('file_size', 'desc');
                break;
            default:
                $query->orderBy('backup_date', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new database backup.
     */
    public function createBackup(?int $userId = null): DatabaseBackup
    {
        $uniqueId = Str::random(16);
        $timestamp = now()->format('Y_m_d_H_i_s');
        $filename = "backup_{$timestamp}_{$uniqueId}.sql";
        $path = "backups/{$filename}";

        $backup = DatabaseBackup::create([
            'filename' => $filename,
            'unique_identifier' => $uniqueId,
            'path' => $path,
            'file_size' => 0,
            'status' => 'pending',
            'backup_date' => now(),
            'created_by' => $userId,
        ]);

        return $backup;
    }

    /**
     * Execute database backup process.
     */
    public function executeBackup(DatabaseBackup $backup): bool
    {
        try {
            // Update status to in_progress
            $backup->update(['status' => 'in_progress']);

            // Get database configuration
            $database = config('database.default');
            $connection = config("database.connections.{$database}");

            // Validate required configuration
            if (empty($connection['database'])) {
                throw new \Exception("Database name is not configured");
            }

            // Ensure backups directory exists
            $backupDir = Storage::disk('local')->path('backups');
            if (!file_exists($backupDir)) {
                if (!mkdir($backupDir, 0755, true)) {
                    throw new \Exception("Failed to create backup directory: {$backupDir}");
                }
            }

            $backupFile = Storage::disk('local')->path($backup->path);

            // Get mysqldump executable path
            $mysqldumpPath = $this->getMysqldumpPath();
            if (!$mysqldumpPath) {
                throw new \Exception("mysqldump command not found. Please ensure MySQL client tools are installed.");
            }

            // Build mysqldump command with proper escaping
            $username = $connection['username'];
            $password = $connection['password'] ?? '';
            $host = $connection['host'];
            $port = $connection['port'] ?? '3306';
            $database = $connection['database'];

            // Use different command format based on OS
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows command (Laragon uses backslashes in paths)
                $command = sprintf(
                    '"%s" --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --lock-tables=false %s > "%s" 2>&1',
                    $mysqldumpPath,
                    $username,
                    $password,
                    $host,
                    $port,
                    $database,
                    $backupFile
                );
            } else {
                // Linux/Mac command with proper escaping
                $command = sprintf(
                    '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --lock-tables=false %s > %s 2>&1',
                    escapeshellarg($mysqldumpPath),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($database),
                    escapeshellarg($backupFile)
                );
            }

            // Execute backup command
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $errorMessage = "Backup failed with exit code {$returnCode}";
                if (!empty($output)) {
                    $errorMessage .= ": " . implode("\n", $output);
                }
                throw new \Exception($errorMessage);
            }

            // Verify file was created and get size
            if (!file_exists($backupFile)) {
                throw new \Exception("Backup file was not created at: {$backupFile}");
            }

            $fileSize = filesize($backupFile);

            if ($fileSize === false) {
                throw new \Exception("Failed to get backup file size");
            }

            if ($fileSize === 0) {
                throw new \Exception("Backup file is empty. Check database connection and permissions.");
            }

            // Update backup record
            $backup->update([
                'status' => 'completed',
                'file_size' => $fileSize,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            return true;

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Database backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update backup with error
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Delete partial backup file if exists
            if (isset($backupFile) && file_exists($backupFile)) {
                @unlink($backupFile);
            }

            throw $e;
        }
    }

    /**
     * Restore database from backup.
     */
    public function restoreFromBackup(DatabaseBackup $backup): bool
    {
        if (!$backup->fileExists()) {
            throw new \Exception("Backup file not found");
        }

        if (!$backup->isCompleted() && !$backup->isInTrash()) {
            throw new \Exception("Cannot restore from incomplete or failed backup");
        }

        try {
            // Get database configuration
            $database = config('database.default');
            $connection = config("database.connections.{$database}");

            $backupFile = $backup->getFullPath();

            // Build mysql restore command
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($connection['username']),
                escapeshellarg($connection['password']),
                escapeshellarg($connection['host']),
                escapeshellarg($connection['port'] ?? '3306'),
                escapeshellarg($connection['database']),
                escapeshellarg($backupFile)
            );

            // Execute restore command
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("Restore failed with exit code {$returnCode}: " . implode("\n", $output));
            }

            return true;

        } catch (\Exception $e) {
            throw new \Exception("Failed to restore database: " . $e->getMessage());
        }
    }

    /**
     * Delete backup and file.
     */
    public function deleteBackup(DatabaseBackup $backup, bool $deleteFile = true): bool
    {
        if ($deleteFile && $backup->fileExists()) {
            Storage::disk('local')->delete($backup->path);
        }

        return $backup->delete();
    }

    /**
     * Move backup to trash.
     */
    public function moveToTrash(DatabaseBackup $backup): bool
    {
        return $backup->moveToTrash();
    }

    /**
     * Restore backup from trash.
     */
    public function restoreFromTrash(DatabaseBackup $backup): bool
    {
        if (!$backup->isInTrash()) {
            throw new \Exception("Backup is not in trash");
        }

        return $backup->restoreFromTrash();
    }

    /**
     * Process auto-trash for old backups (older than 15 days).
     */
    public function processAutoTrash(): array
    {
        $backups = DatabaseBackup::eligibleForAutoTrash()->get();
        $movedCount = 0;

        foreach ($backups as $backup) {
            if ($backup->moveToTrash()) {
                $movedCount++;
            }
        }

        return [
            'total' => $backups->count(),
            'moved' => $movedCount,
        ];
    }

    /**
     * Process permanent deletion for old trash items (in trash > 7 days).
     */
    public function processPermanentDeletion(): array
    {
        $backups = DatabaseBackup::eligibleForPermanentDeletion()->get();
        $deletedCount = 0;

        foreach ($backups as $backup) {
            if ($this->deleteBackup($backup, true)) {
                $deletedCount++;
            }
        }

        return [
            'total' => $backups->count(),
            'deleted' => $deletedCount,
        ];
    }

    /**
     * Get backup statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_backups' => DatabaseBackup::count(),
            'completed_backups' => DatabaseBackup::where('status', 'completed')->count(),
            'failed_backups' => DatabaseBackup::where('status', 'failed')->count(),
            'trashed_backups' => DatabaseBackup::trashed()->count(),
            'total_size' => DatabaseBackup::completed()->sum('file_size'),
            'average_size' => DatabaseBackup::completed()->avg('file_size'),
            'latest_backup' => DatabaseBackup::completed()
                ->orderBy('backup_date', 'desc')
                ->first(),
            'oldest_backup' => DatabaseBackup::completed()
                ->orderBy('backup_date', 'asc')
                ->first(),
        ];
    }

    /**
     * Download backup file.
     */
    public function downloadBackup(DatabaseBackup $backup): array
    {
        if (!$backup->fileExists()) {
            throw new \Exception("Backup file not found");
        }

        return [
            'path' => $backup->getFullPath(),
            'filename' => $backup->filename,
            'mime' => 'application/sql',
        ];
    }

    /**
     * Get mysqldump executable path based on OS and environment.
     */
    private function getMysqldumpPath(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Try Laragon paths first (check multiple MySQL versions)
            $laragonBase = 'C:\laragon\bin\mysql';
            
            if (is_dir($laragonBase)) {
                $mysqlDirs = glob($laragonBase . '\*', GLOB_ONLYDIR);
                foreach ($mysqlDirs as $dir) {
                    $mysqldumpPath = $dir . '\bin\mysqldump.exe';
                    if (file_exists($mysqldumpPath)) {
                        return $mysqldumpPath;
                    }
                }
            }
            
            // Try default Windows PATH
            $result = shell_exec('where mysqldump 2>&1');
            if ($result && strpos($result, 'mysqldump') !== false) {
                return trim(explode("\n", $result)[0]);
            }
            
            return null;
        } else {
            // Linux/Mac - use which command
            $result = shell_exec('which mysqldump 2>&1');
            if ($result && strpos($result, '/') !== false) {
                return trim($result);
            }
            
            // Try common paths
            $commonPaths = [
                '/usr/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                '/usr/local/mysql/bin/mysqldump',
            ];
            
            foreach ($commonPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
            
            return null;
        }
    }
}
