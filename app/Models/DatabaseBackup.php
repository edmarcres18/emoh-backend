<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'path',
        'size',
        'created_by',
        'trashed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trashed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this backup.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if backup is in trash.
     */
    public function isTrashed(): bool
    {
        return $this->trashed_at !== null;
    }

    /**
     * Scope to get only active (non-trashed) backups.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('trashed_at');
    }

    /**
     * Scope to get only trashed backups.
     */
    public function scopeTrashed($query)
    {
        return $query->whereNotNull('trashed_at');
    }

    /**
     * Scope to get backups older than specified days.
     */
    public function scopeOlderThan($query, int $days)
    {
        return $query->where('created_at', '<=', now()->subDays($days));
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
