# Database Backup System Migration

## Overview
The database backup system has been completely refactored to match the approach used in mhrhci-backend. The new implementation is simpler, more maintainable, and uses direct filesystem operations instead of database tracking.

## Key Changes

### Architecture Changes
- **Removed database tracking**: No longer uses `database_backups` table to track backups
- **File-based operations**: Backups are managed directly through the filesystem
- **Simplified approach**: Single controller handles all backup operations without service layer
- **Filename-based routing**: Routes now use filenames instead of database IDs

### Updated Files

#### Backend (PHP)
1. **DatabaseBackupController** (`app/Http/Controllers/DatabaseBackupController.php`)
   - Completely rewritten to handle backups without database tracking
   - Uses direct filesystem operations
   - Supports MySQL, PostgreSQL, and SQLite databases
   - Includes fallback PHP-based backup for MySQL when mysqldump unavailable
   - Methods:
     - `index()` - Display backup management page
     - `backup()` - Create new backup
     - `download($filename)` - Download backup file
     - `destroy($filename)` - Delete backup file
     - `restore()` - Restore from backup
     - `uploadAndRestore()` - Upload and restore from external SQL file

2. **DatabaseBackupCommand** (`app/Console/Commands/DatabaseBackupCommand.php`)
   - Renamed from `backup:create` to `backup:database`
   - Added `--keep-days` option for automatic cleanup
   - Automatically cleans old backups during execution
   - Uses reflection to access controller's private backup methods

3. **Routes** (`routes/web.php`)
   - Changed from ID-based to filename-based routes
   - Routes:
     - `GET /admin/database-backup` - Index page
     - `POST /admin/database-backup/create` - Create backup
     - `GET /admin/database-backup/download/{filename}` - Download
     - `DELETE /admin/database-backup/{filename}` - Delete
     - `POST /admin/database-backup/restore` - Restore from backup
     - `POST /admin/database-backup/upload-restore` - Upload & restore

4. **Console Scheduler** (`routes/console.php`)
   - Added scheduled daily backup at 2:00 AM
   - Keeps backups for 30 days
   - Includes success/failure logging

#### Frontend (Vue)
1. **Backup.vue** (`resources/js/pages/Database/Backup.vue`)
   - Completely new UI matching mhrhci-backend design
   - Clean, modern interface with dark mode support
   - Features:
     - Database information display (driver, name, tables, size)
     - Create backup button
     - Upload & restore functionality
     - Backup list with actions (download, restore, delete)
     - Confirmation modals for destructive actions
     - Toast notifications for feedback
     - Responsive design (mobile and desktop views)

2. **Toast.vue** (`resources/js/pages/Database/Toast.vue`)
   - New toast notification component
   - Success and error states
   - Auto-dismiss with manual close option

### Files to Remove (Manual Cleanup Required)

The following files are no longer needed and should be deleted:

```
app/Services/DatabaseBackupService.php
app/Models/DatabaseBackup.php
app/Console/Commands/BackupCleanupCommand.php
app/Console/Commands/RunScheduledBackups.php
resources/js/pages/DatabaseBackup/ (entire directory)
database/factories/DatabaseBackupFactory.php
database/migrations/*_create_database_backups_table.php (if exists)
```

### Database Cleanup

If the `database_backups` table exists, you can drop it:

```sql
DROP TABLE IF EXISTS database_backups;
```

## Features

### Current Features
- ✅ Create manual backups via UI
- ✅ Scheduled automated backups (daily at 2:00 AM)
- ✅ Download backups
- ✅ Delete backups
- ✅ Restore from backup
- ✅ Upload and restore external SQL files
- ✅ Automatic cleanup of old backups (30 days)
- ✅ Support for MySQL, PostgreSQL, and SQLite
- ✅ Fallback PHP-based backup for MySQL
- ✅ Database information display
- ✅ Permission checks (Admin only)
- ✅ Responsive UI with dark mode

### Removed Features
- ❌ Database tracking of backups
- ❌ Backup status tracking (pending, in_progress, completed, failed)
- ❌ Soft delete / trash functionality
- ❌ Scheduled backups from UI
- ❌ Backup types (manual vs scheduled)
- ❌ Pagination of backups
- ❌ Search and filtering

## Usage

### Manual Backup via UI
1. Navigate to Admin → Database Backup
2. Click "Create Backup" button
3. Backup will be created and displayed in the list

### Manual Backup via CLI
```bash
php artisan backup:database
```

With custom retention:
```bash
php artisan backup:database --keep-days=60
```

### Scheduled Backups
Backups run automatically daily at 2:00 AM. Ensure Laravel scheduler is running:
```bash
php artisan schedule:work
```

Or add to crontab:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Restore from Backup
1. Navigate to Admin → Database Backup
2. Click "Restore" on desired backup
3. Confirm the action
4. Database will be restored

### Upload External Backup
1. Navigate to Admin → Database Backup
2. Click "Upload & Restore"
3. Select SQL file
4. Confirm to restore

## Storage Location
Backups are stored in:
```
storage/app/backups/database/
```

Filename format:
```
emoh_backup_YY_MM_DD_HHmmss.sql
```

Example: `emoh_backup_25_10_16_143052.sql`

## Permissions
Only users with admin privileges can access backup management:
- System Admin
- Admin

## Technical Notes

### Backup Methods
- **MySQL**: Uses `mysqldump` command, falls back to PHP-based backup if unavailable
- **PostgreSQL**: Uses `pg_dump` command
- **SQLite**: Direct SQL dump via Laravel DB connection

### Security
- Filename validation to prevent directory traversal
- Admin-only access
- File type validation (`.sql` only)
- Size limits on uploads (100MB max)

### Process Flow
1. User requests backup creation
2. Controller determines database driver
3. Appropriate backup method called
4. SQL file created in storage directory
5. File listed in UI by scanning directory

## Migration Steps

If migrating from old system:

1. ✅ Update `DatabaseBackupController.php`
2. ✅ Update `DatabaseBackupCommand.php`
3. ✅ Update routes in `web.php`
4. ✅ Update `console.php` scheduler
5. ✅ Create new `Database/Backup.vue`
6. ✅ Create new `Database/Toast.vue`
7. ⚠️ Delete old service, model, commands (see list above)
8. ⚠️ Drop `database_backups` table if exists
9. ✅ Test backup creation
10. ✅ Test backup restore
11. ✅ Test backup download
12. ✅ Test backup deletion

## Benefits of New Approach

1. **Simplicity**: No database tracking overhead
2. **Reliability**: Direct filesystem operations are more reliable
3. **Performance**: Faster listing (no database queries)
4. **Maintainability**: Less code, easier to understand
5. **Consistency**: Matches mhrhci-backend implementation
6. **Portability**: Backups are just files, easy to move/copy

## Troubleshooting

### mysqldump not found
If mysqldump is not in PATH, the system will automatically fall back to PHP-based backup.

### Permission errors
Ensure `storage/app/backups/database/` is writable:
```bash
chmod -R 775 storage/app/backups
```

### Large database timeout
Increase timeout in controller if needed (default: 300 seconds)

### Restore fails
- Ensure SQL file is compatible with database driver
- Check PHP memory_limit and max_execution_time
- Verify database user has necessary privileges

## Future Enhancements
- Add backup compression (gzip)
- Add backup encryption
- Add cloud storage integration (S3, etc.)
- Add backup verification
- Add email notifications
