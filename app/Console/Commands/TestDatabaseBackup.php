<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TestDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test database backup configuration and requirements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Database Backup System...');
        $this->newLine();

        $allPassed = true;

        // Test 1: Check database connection
        $this->info('1. Testing database connection...');
        try {
            DB::connection()->getPdo();
            $this->info('   ✓ Database connection successful');
        } catch (\Exception $e) {
            $this->error('   ✗ Database connection failed: ' . $e->getMessage());
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: Check database configuration
        $this->info('2. Checking database configuration...');
        $database = config('database.default');
        $connection = config("database.connections.{$database}");
        
        $this->line("   Database: {$connection['database']}");
        $this->line("   Host: {$connection['host']}");
        $this->line("   Port: " . ($connection['port'] ?? '3306'));
        $this->line("   Username: {$connection['username']}");
        $this->info('   ✓ Configuration loaded');
        $this->newLine();

        // Test 3: Check mysqldump availability
        $this->info('3. Checking mysqldump availability...');
        $mysqldumpPath = $this->findMysqldump();
        if ($mysqldumpPath) {
            $this->info("   ✓ mysqldump found at: {$mysqldumpPath}");
        } else {
            $this->error('   ✗ mysqldump not found');
            $this->error('   Please install MySQL client tools');
            $allPassed = false;
        }
        $this->newLine();

        // Test 4: Check storage directory
        $this->info('4. Checking backup storage directory...');
        $backupDir = Storage::disk('local')->path('backups');
        
        if (!file_exists($backupDir)) {
            if (mkdir($backupDir, 0755, true)) {
                $this->info("   ✓ Created backup directory: {$backupDir}");
            } else {
                $this->error("   ✗ Failed to create backup directory: {$backupDir}");
                $allPassed = false;
            }
        } else {
            $this->info("   ✓ Backup directory exists: {$backupDir}");
        }

        if (is_writable($backupDir)) {
            $this->info('   ✓ Backup directory is writable');
        } else {
            $this->error('   ✗ Backup directory is not writable');
            $this->error('   Run: chmod -R 775 storage/app/backups');
            $allPassed = false;
        }
        $this->newLine();

        // Test 5: Check disk space
        $this->info('5. Checking disk space...');
        $freeSpace = disk_free_space($backupDir);
        $totalSpace = disk_total_space($backupDir);
        $usedSpace = $totalSpace - $freeSpace;
        $percentUsed = round(($usedSpace / $totalSpace) * 100, 2);

        $this->line('   Total: ' . $this->formatBytes($totalSpace));
        $this->line('   Used: ' . $this->formatBytes($usedSpace) . " ({$percentUsed}%)");
        $this->line('   Free: ' . $this->formatBytes($freeSpace));

        if ($freeSpace > 100 * 1024 * 1024) { // 100MB
            $this->info('   ✓ Sufficient disk space available');
        } else {
            $this->warn('   ⚠ Low disk space available');
        }
        $this->newLine();

        // Test 6: Test mysqldump command
        if ($mysqldumpPath) {
            $this->info('6. Testing mysqldump command...');
            $testFile = $backupDir . '/test_backup_' . time() . '.sql';
            
            $command = $this->buildTestCommand($mysqldumpPath, $connection, $testFile);
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($testFile)) {
                $fileSize = filesize($testFile);
                $this->info("   ✓ Test backup created successfully ({$this->formatBytes($fileSize)})");
                
                // Clean up test file
                @unlink($testFile);
                $this->info('   ✓ Test file cleaned up');
            } else {
                $this->error('   ✗ Test backup failed');
                if (!empty($output)) {
                    $this->error('   Error: ' . implode("\n   ", $output));
                }
                $allPassed = false;
            }
            $this->newLine();
        }

        // Summary
        $this->newLine();
        if ($allPassed) {
            $this->info('✓ All tests passed! Backup system is ready.');
            return self::SUCCESS;
        } else {
            $this->error('✗ Some tests failed. Please fix the issues above.');
            return self::FAILURE;
        }
    }

    private function findMysqldump(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - Try Laragon
            $laragonBase = 'C:\laragon\bin\mysql';
            if (is_dir($laragonBase)) {
                $mysqlDirs = glob($laragonBase . '\*', GLOB_ONLYDIR);
                foreach ($mysqlDirs as $dir) {
                    $path = $dir . '\bin\mysqldump.exe';
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
            
            // Try PATH
            $result = shell_exec('where mysqldump 2>&1');
            if ($result && strpos($result, 'mysqldump') !== false) {
                return trim(explode("\n", $result)[0]);
            }
        } else {
            // Linux/Mac
            $result = shell_exec('which mysqldump 2>&1');
            if ($result && strpos($result, '/') !== false) {
                return trim($result);
            }
            
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
        }
        
        return null;
    }

    private function buildTestCommand(string $mysqldumpPath, array $connection, string $testFile): string
    {
        $username = $connection['username'];
        $password = $connection['password'] ?? '';
        $host = $connection['host'];
        $port = $connection['port'] ?? '3306';
        $database = $connection['database'];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return sprintf(
                '"%s" --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --lock-tables=false %s > "%s" 2>&1',
                $mysqldumpPath,
                $username,
                $password,
                $host,
                $port,
                $database,
                $testFile
            );
        } else {
            return sprintf(
                '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --lock-tables=false %s > %s 2>&1',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($testFile)
            );
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
