<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseBackupController extends Controller
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display the backup management page.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', DatabaseBackup::class);

        return Inertia::render('Admin/DatabaseBackups/Index');
    }

    /**
     * Get paginated list of backups (API endpoint).
     */
    public function list(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseBackup::class);

        try {
            $filters = $request->only([
                'search', 'sort', 'status', 'view', 'date_from', 'date_to'
            ]);

            $backups = $this->backupService->getPaginatedBackups($filters, 10);

            return response()->json([
                'success' => true,
                'data' => $backups,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch backups.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new database backup.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', DatabaseBackup::class);

        try {
            // Create backup record
            $backup = $this->backupService->createBackup(auth()->id());

            // Execute backup synchronously
            $this->backupService->executeBackup($backup);

            return response()->json([
                'success' => true,
                'message' => 'Database backup created successfully.',
                'data' => $backup->fresh()->load('creator:id,name,email'),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Backup creation failed in controller', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show backup details.
     */
    public function show(DatabaseBackup $backup): JsonResponse
    {
        Gate::authorize('view', $backup);

        try {
            $backup->load('creator:id,name,email');

            return response()->json([
                'success' => true,
                'data' => $backup,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch backup details.',
            ], 500);
        }
    }

    /**
     * Download backup file.
     */
    public function download(DatabaseBackup $backup)
    {
        Gate::authorize('download', $backup);

        try {
            $fileInfo = $this->backupService->downloadBackup($backup);

            return response()->download(
                $fileInfo['path'],
                $fileInfo['filename'],
                ['Content-Type' => $fileInfo['mime']]
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore database from backup.
     */
    public function restore(DatabaseBackup $backup): JsonResponse
    {
        Gate::authorize('restore', $backup);

        try {
            if (!$backup->fileExists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found.',
                ], 404);
            }

            if (!$backup->isCompleted() && !$backup->isInTrash()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot restore from incomplete or failed backup.',
                ], 400);
            }

            // Execute restore synchronously
            $this->backupService->restoreFromBackup($backup);

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move backup to trash.
     */
    public function trash(DatabaseBackup $backup): JsonResponse
    {
        Gate::authorize('trash', $backup);

        try {
            $this->backupService->moveToTrash($backup);

            return response()->json([
                'success' => true,
                'message' => 'Backup moved to trash successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move backup to trash.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore backup from trash.
     */
    public function restoreFromTrash(DatabaseBackup $backup): JsonResponse
    {
        Gate::authorize('restoreFromTrash', $backup);

        try {
            $this->backupService->restoreFromTrash($backup);

            return response()->json([
                'success' => true,
                'message' => 'Backup restored from trash successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup from trash.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permanently delete backup.
     */
    public function destroy(DatabaseBackup $backup): JsonResponse
    {
        Gate::authorize('delete', $backup);

        try {
            $this->backupService->deleteBackup($backup, true);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted permanently.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup statistics.
     */
    public function statistics(): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseBackup::class);

        try {
            $stats = $this->backupService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics.',
            ], 500);
        }
    }

    /**
     * Bulk delete backups.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseBackup::class);

        $request->validate([
            'backup_ids' => 'required|array',
            'backup_ids.*' => 'exists:database_backups,id',
        ]);

        try {
            $backups = DatabaseBackup::whereIn('id', $request->backup_ids)->get();
            $deletedCount = 0;

            foreach ($backups as $backup) {
                if (Gate::allows('delete', $backup)) {
                    $this->backupService->deleteBackup($backup, true);
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} backup(s).",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backups.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk move backups to trash.
     */
    public function bulkTrash(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DatabaseBackup::class);

        $request->validate([
            'backup_ids' => 'required|array',
            'backup_ids.*' => 'exists:database_backups,id',
        ]);

        try {
            $backups = DatabaseBackup::whereIn('id', $request->backup_ids)->get();
            $trashedCount = 0;

            foreach ($backups as $backup) {
                if (Gate::allows('trash', $backup)) {
                    $this->backupService->moveToTrash($backup);
                    $trashedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully moved {$trashedCount} backup(s) to trash.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move backups to trash.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
