<?php

namespace App\Http\Controllers;

use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display a listing of database backups.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'latest'),
            'trash' => $request->get('trash', false),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
        ];

        $backups = $this->backupService->getBackups($filters, 15);

        // Transform the data for the frontend
        $transformedBackups = [
            'data' => $backups->items(),
            'current_page' => $backups->currentPage(),
            'last_page' => $backups->lastPage(),
            'per_page' => $backups->perPage(),
            'total' => $backups->total(),
            'from' => $backups->firstItem(),
            'to' => $backups->lastItem(),
            'links' => $backups->linkCollection()->toArray(),
        ];

        return Inertia::render('DatabaseBackup/Index', [
            'backups' => $transformedBackups,
            'filters' => $filters
        ]);
    }

    /**
     * Store a newly created database backup.
     */
    public function store(Request $request)
    {
        try {
            $type = $request->get('type', 'manual');
            $scheduledAt = $request->get('scheduled_at') ?
                \Carbon\Carbon::parse($request->get('scheduled_at')) : null;

            $backup = $this->backupService->createBackup($type, $scheduledAt);

            $message = $type === 'scheduled'
                ? "Database backup scheduled successfully for {$scheduledAt->format('Y-m-d H:i:s')}!"
                : 'Database backup created successfully!';

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create database backup: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified database backup (move to trash).
     */
    public function destroy(int $id)
    {
        try {
            $this->backupService->softDeleteBackup($id);
            return redirect()->back()->with('success', 'Database backup moved to trash successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to move backup to trash: ' . $e->getMessage());
        }
    }

    /**
     * Restore a backup from trash.
     */
    public function restore(int $id)
    {
        try {
            $this->backupService->restoreBackup($id);
            return redirect()->back()->with('success', 'Database backup restored successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a backup from trash.
     */
    public function forceDelete(int $id)
    {
        try {
            $this->backupService->permanentlyDeleteBackup($id);
            return redirect()->back()->with('success', 'Database backup permanently deleted!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to permanently delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Download the specified database backup.
     */
    public function download(int $id): BinaryFileResponse
    {
        try {
            $backup = DatabaseBackup::findOrFail($id);

            if (!$backup->isCompleted()) {
                abort(404, 'Backup not completed or not available for download');
            }

            if (!file_exists($backup->file_path)) {
                abort(404, 'Backup file not found');
            }

            return response()->download($backup->file_path, $backup->filename, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $backup->filename . '"'
            ]);

        } catch (\Exception $e) {
            abort(404, 'Backup file not found');
        }
    }
}
