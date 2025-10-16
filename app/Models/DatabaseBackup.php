<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'unique_identifier',
        'path',
        'file_size',
        'status',
        'error_message',
        'backup_date',
        'trashed_at',
        'completed_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'backup_date' => 'datetime',
            'trashed_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who created the backup.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if backup is in trash.
     */
    public function isInTrash(): bool
    {
        return $this->status === 'in_trash' && $this->trashed_at !== null;
    }

    /**
     * Check if backup is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if backup has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Move backup to trash.
     */
    public function moveToTrash(): bool
    {
        return $this->update([
            'status' => 'in_trash',
            'trashed_at' => now(),
        ]);
    }

    /**
     * Restore backup from trash.
     */
    public function restoreFromTrash(): bool
    {
        return $this->update([
            'status' => 'completed',
            'trashed_at' => null,
        ]);
    }

    /**
     * Get file exists status.
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->path);
    }

    /**
     * Get full file path.
     */
    public function getFullPath(): string
    {
        return Storage::disk('local')->path($this->path);
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if backup is eligible for auto-trash (older than 15 days).
     */
    public function isEligibleForAutoTrash(): bool
    {
        return $this->status === 'completed' 
            && $this->backup_date->lessThan(now()->subDays(15));
    }

    /**
     * Check if trashed backup is eligible for permanent deletion (in trash > 7 days).
     */
    public function isEligibleForPermanentDeletion(): bool
    {
        return $this->isInTrash() 
            && $this->trashed_at !== null 
            && $this->trashed_at->lessThan(now()->subDays(7));
    }

    /**
     * Scope to get only completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get only trashed backups.
     */
    public function scopeTrashed($query)
    {
        return $query->where('status', 'in_trash')->whereNotNull('trashed_at');
    }

    /**
     * Scope to get backups eligible for auto-trash.
     */
    public function scopeEligibleForAutoTrash($query)
    {
        return $query->where('status', 'completed')
            ->where('backup_date', '<', now()->subDays(15));
    }

    /**
     * Scope to get trashed backups eligible for permanent deletion.
     */
    public function scopeEligibleForPermanentDeletion($query)
    {
        return $query->where('status', 'in_trash')
            ->whereNotNull('trashed_at')
            ->where('trashed_at', '<', now()->subDays(7));
    }
}
