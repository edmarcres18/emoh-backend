<?php

namespace App\Policies;

use App\Models\DatabaseBackup;
use App\Models\User;

class DatabaseBackupPolicy
{
    /**
     * Determine if the user can view any backups.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can view the backup.
     */
    public function view(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can create backups.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can download backups.
     */
    public function download(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can restore backups.
     */
    public function restore(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can move backups to trash.
     */
    public function trash(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can restore from trash.
     */
    public function restoreFromTrash(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine if the user can permanently delete backups.
     */
    public function delete(User $user, DatabaseBackup $backup): bool
    {
        return $user->hasRole('System Admin');
    }
}
