<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Display a listing of the backups.
     */
    public function index(Request $request): Response
    {
        // Only System Admin can access
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can access database backups');
        }

        $query = DatabaseBackup::with('creator:id,name,email');

        // Filter by status (active or trashed)
        if ($request->has('status')) {
            if ($request->status === 'trashed') {
                $query->trashed();
            } elseif ($request->status === 'active') {
                $query->active();
            }
        } else {
            // Default to active backups
            $query->active();
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('filename', 'like', '%' . $request->search . '%')
                  ->orWhereHas('creator', function ($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%')
                         ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Sort functionality
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'largest':
                $query->orderBy('size', 'desc');
                break;
            case 'smallest':
                $query->orderBy('size', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('filename', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('filename', 'desc');
                break;
            default:
                $query->latest();
        }

        $backups = $query->paginate($request->get('per_page', 15));

        // Add formatted size to each backup
        $backups->getCollection()->transform(function ($backup) {
            $backup->formatted_size = $backup->formatted_size;
            return $backup;
        });

        return Inertia::render('Admin/DatabaseBackups/Index', [
            'backups' => $backups,
            'filters' => $request->only(['search', 'sort', 'status']),
            'stats' => [
                'total_active' => DatabaseBackup::active()->count(),
                'total_trashed' => DatabaseBackup::trashed()->count(),
                'total_size' => DatabaseBackup::active()->sum('size'),
            ]
        ]);
    }

    /**
     * Create a new database backup.
     */
    public function store(Request $request)
    {
        // Only System Admin can create backups
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can create database backups');
        }

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
            $filename = "backup_{$dbName}_{$timestamp}.sql";
            
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

            // Execute backup command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($backupPath)) {
                throw new \Exception('Backup creation failed: ' . implode("\n", $output));
            }

            // Get file size
            $fileSize = filesize($backupPath);

            // Save backup record to database
            $backup = DatabaseBackup::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => $fileSize,
                'created_by' => auth()->id(),
            ]);

            return back()->with('success', 'Database backup created successfully: ' . $filename);

        } catch (\Exception $e) {
            \Log::error('Database backup failed: ' . $e->getMessage());
            
            // Clean up partial backup file if it exists
            if (isset($backupPath) && file_exists($backupPath)) {
                unlink($backupPath);
            }

            return back()->with('error', 'Failed to create database backup: ' . $e->getMessage());
        }
    }

    /**
     * Download a backup file.
     */
    public function download(DatabaseBackup $backup): StreamedResponse
    {
        // Only System Admin can download backups
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can download database backups');
        }

        $filePath = storage_path('app/' . $backup->path);

        if (!file_exists($filePath)) {
            abort(404, 'Backup file not found');
        }

        return response()->stream(function () use ($filePath) {
            $stream = fopen($filePath, 'r');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $backup->filename . '"',
            'Content-Length' => filesize($filePath),
        ]);
    }

    /**
     * Restore database from backup.
     */
    public function restore(DatabaseBackup $backup)
    {
        // Only System Admin can restore backups
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can restore database backups');
        }

        // Cannot restore trashed backups
        if ($backup->isTrashed()) {
            return back()->with('error', 'Cannot restore a trashed backup. Please restore it from trash first.');
        }

        try {
            $filePath = storage_path('app/' . $backup->path);

            if (!file_exists($filePath)) {
                return back()->with('error', 'Backup file not found on disk');
            }

            // Get database configuration
            $database = Config::get('database.connections.' . Config::get('database.default'));
            $dbName = $database['database'];
            $dbUser = $database['username'];
            $dbPassword = $database['password'];
            $dbHost = $database['host'];
            $dbPort = $database['port'] ?? 3306;

            // Build mysql restore command
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($dbUser),
                escapeshellarg($dbPassword),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($filePath)
            );

            // Execute restore command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Database restore failed: ' . implode("\n", $output));
            }

            return back()->with('success', 'Database restored successfully from backup: ' . $backup->filename);

        } catch (\Exception $e) {
            \Log::error('Database restore failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to restore database: ' . $e->getMessage());
        }
    }

    /**
     * Move backup to trash.
     */
    public function trash(DatabaseBackup $backup)
    {
        // Only System Admin can trash backups
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can trash database backups');
        }

        // Check if already trashed
        if ($backup->isTrashed()) {
            return back()->with('error', 'Backup is already in trash');
        }

        $backup->update([
            'trashed_at' => now()
        ]);

        return back()->with('success', 'Backup moved to trash successfully');
    }

    /**
     * Restore backup from trash.
     */
    public function restoreFromTrash(DatabaseBackup $backup)
    {
        // Only System Admin can restore from trash
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can restore backups from trash');
        }

        // Check if in trash
        if (!$backup->isTrashed()) {
            return back()->with('error', 'Backup is not in trash');
        }

        $backup->update([
            'trashed_at' => null
        ]);

        return back()->with('success', 'Backup restored from trash successfully');
    }

    /**
     * Permanently delete a backup.
     */
    public function destroy(DatabaseBackup $backup)
    {
        // Only System Admin can delete backups
        if (!auth()->user()->hasRole('System Admin')) {
            abort(403, 'Only System Admin can delete database backups');
        }

        try {
            // Delete physical file
            $filePath = storage_path('app/' . $backup->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete database record
            $backup->delete();

            return back()->with('success', 'Backup permanently deleted successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to delete backup: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Get backup statistics.
     */
    public function stats()
    {
        // Only System Admin can view stats
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can view backup statistics'
            ], 403);
        }

        return response()->json([
            'total_active' => DatabaseBackup::active()->count(),
            'total_trashed' => DatabaseBackup::trashed()->count(),
            'total_size' => DatabaseBackup::active()->sum('size'),
            'oldest_backup' => DatabaseBackup::active()->oldest()->first(),
            'latest_backup' => DatabaseBackup::active()->latest()->first(),
        ]);
    }
}
