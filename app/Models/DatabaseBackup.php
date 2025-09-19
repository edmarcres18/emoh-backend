<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatabaseBackup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'filename',
        'file_path',
        'file_size',
        'status',
        'error_message',
        'type',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if backup is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if backup is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if backup is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->type === 'scheduled';
    }

    /**
     * Check if backup is manual.
     */
    public function isManual(): bool
    {
        return $this->type === 'manual';
    }

    /**
     * Scope for active backups (not deleted).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope for trash backups (soft deleted).
     */
    public function scopeTrash($query)
    {
        return $query->onlyTrashed();
    }

    /**
     * Scope for completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for scheduled backups.
     */
    public function scopeScheduled($query)
    {
        return $query->where('type', 'scheduled');
    }
}
