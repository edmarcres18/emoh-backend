# Database Backup System - Setup Guide

## Overview
This system provides comprehensive database backup management with:
- ✅ Create backups and store as .sql files in storage
- ✅ Restore database from backups
- ✅ Download backup files
- ✅ Trash system (15-day retention before moving to trash)
- ✅ Permanent deletion (7 days after being trashed)
- ✅ Scheduled automatic backups (daily at 2:00 AM)
- ✅ Scheduled cleanup (daily at 3:00 AM)
- ✅ System Admin only access
- ✅ Responsive and real-time UI
- ✅ Production-ready with proper error handling

## Installation Steps

### 1. Run Database Migration
```bash
php artisan migrate
```

This creates the `database_backups` table with:
- `filename` - Backup file name
- `path` - Storage path
- `size` - File size in bytes
- `created_by` - User who created the backup
- `trashed_at` - Trash timestamp for soft deletion
- Proper indexes for performance

### 2. Create Storage Directory
```bash
mkdir -p storage/app/backups
chmod 755 storage/app/backups
```

### 3. Verify Database Credentials
Ensure your `.env` file has proper MySQL credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Verify MySQL Tools
The system uses `mysqldump` and `mysql` commands. Verify they're available:
```bash
# Windows (Laragon/XAMPP)
where mysqldump
where mysql

# Linux/Mac
which mysqldump
which mysql
```

If not found, ensure MySQL bin directory is in your PATH.

### 5. Build Frontend Assets
```bash
npm run build
# or for development
npm run dev
```

### 6. Set Up Scheduled Tasks

#### Windows (Laragon/XAMPP)
Add to Windows Task Scheduler:
```
Program: C:\laragon\bin\php\php-8.x.x\php.exe
Arguments: C:\laragon\www\emoh\emoh-backend\artisan schedule:run
Start in: C:\laragon\www\emoh\emoh-backend
Trigger: Daily, repeat every 1 minute
```

#### Linux/Production
Add to crontab:
```bash
* * * * * cd /path/to/emoh-backend && php artisan schedule:run >> /dev/null 2>&1
```

## Features

### 1. Manual Backup Creation
- Click "Create Backup" button in the UI
- System creates a timestamped .sql file
- Stored in `storage/app/backups/`
- Database record created with metadata

### 2. Scheduled Backup
- Runs daily at 2:00 AM
- Creates backup with "scheduled_backup_" prefix
- Assigned to first System Admin user
- Runs in background without overlap

### 3. Download Backup
- Click "Download" button on any active backup
- Streams .sql file directly to browser
- No temporary files created

### 4. Restore Database
- Click "Restore DB" button
- Confirmation modal prevents accidental restore
- Replaces current database with backup data
- **WARNING**: This action cannot be undone!

### 5. Trash Management
- Click "Trash" to move backup to trash
- Trashed backups kept for 7 days
- Can restore from trash with "Restore" button
- Filter view by "Active" or "Trashed" status

### 6. Automatic Cleanup
Runs daily at 3:00 AM:
- **Step 1**: Move backups older than 15 days to trash
- **Step 2**: Permanently delete trash older than 7 days
- Deletes both database record and physical file
- Shows detailed cleanup summary in console

### 7. Manual Cleanup Command
```bash
# Use default settings (15 days to trash, 7 days to delete)
php artisan backups:clean

# Custom settings
php artisan backups:clean --trash-days=30 --delete-days=14
```

### 8. Manual Scheduled Backup Command
```bash
php artisan backups:create-scheduled
```

## Security Features

### System Admin Only Access
- All routes protected by `role:System Admin` middleware
- Controller methods verify System Admin role
- UI menu only visible to System Admin
- API endpoints return 403 for non-System Admin

### Protection Mechanisms
- Cannot restore from trashed backups
- Confirmation modals for destructive actions
- File existence checks before operations
- Database transaction support
- Proper error logging

## UI/UX Features

### Responsive Design
- Mobile-friendly table layout
- Touch-friendly action buttons
- Adaptive card grid for stats
- Responsive filters and search

### Real-time Updates
- Inertia.js for SPA experience
- Instant feedback on all actions
- Loading states for async operations
- Toast notifications for success/error

### Consistent with Properties Pages
- Same table structure and styling
- Matching filter/search layout
- Identical button styles
- Unified dark mode support

## Production Considerations

### 1. Large Database Handling
For databases > 1GB:
- Consider compression:
  ```bash
  mysqldump ... | gzip > backup.sql.gz
  ```
- Adjust PHP memory limit in `.env`:
  ```env
  memory_limit=512M
  ```

### 2. Storage Management
- Monitor `storage/app/backups/` size
- Adjust retention periods if needed
- Consider external backup storage (S3, etc.)

### 3. Performance
- Backups run in background
- No user-facing performance impact
- Indexes optimize query performance
- Pagination prevents memory issues

### 4. Monitoring
- Check Laravel logs: `storage/logs/laravel.log`
- Verify scheduled tasks run: `php artisan schedule:list`
- Monitor disk space usage

### 5. Disaster Recovery
- Keep backups in multiple locations
- Test restore process regularly
- Document restore procedures
- Maintain off-site backup copies

## Troubleshooting

### "mysqldump not found" Error
**Windows**: Add MySQL bin to PATH or use full path in command
**Linux**: Install MySQL client: `sudo apt-get install mysql-client`

### "Permission denied" Error
```bash
chmod 755 storage/app/backups
chown -R www-data:www-data storage/app/backups  # Linux
```

### Backup Creation Fails

**"Backup creation failed: " (empty error message)**
This usually means mysqldump command failed. Check:
1. Ensure mysqldump is installed and accessible:
   ```bash
   which mysqldump  # Linux/Mac
   where mysqldump  # Windows
   ```
2. Add mysqldump to PATH if not found
3. Test manually:
   ```bash
   mysqldump --user=YOUR_USER --password=YOUR_PASS --host=localhost YOUR_DB > test.sql
   ```

**Other Backup Creation Issues:**
1. Check MySQL credentials in `.env`
2. Verify database exists
3. Check user permissions: `GRANT SELECT, LOCK TABLES ON database.* TO 'user'@'localhost';`
4. Check Laravel logs for detailed error: `storage/logs/laravel.log`
5. Ensure storage directory is writable:
   ```bash
   chmod 755 storage/app/backups
   chown -R www-data:www-data storage/app/backups  # Linux
   ```

### Restore Fails
1. Ensure backup file exists and is readable
2. Verify MySQL user has appropriate privileges
3. Check for syntax errors in backup file
4. Review error logs

### Schedule Not Running
1. Verify cron job is active: `crontab -l`
2. Check schedule list: `php artisan schedule:list`
3. Manually run: `php artisan schedule:run`
4. Check Laravel logs for errors

## File Locations

- **Migration**: `database/migrations/2025_10_16_061041_create_database_backups_table.php`
- **Model**: `app/Models/DatabaseBackup.php`
- **Controller**: `app/Http/Controllers/Admin/DatabaseBackupController.php`
- **Commands**: 
  - `app/Console/Commands/CleanOldBackupsCommand.php`
  - `app/Console/Commands/CreateScheduledBackupCommand.php`
- **Scheduler**: `app/Console/Kernel.php`
- **Routes**: `routes/web.php` (lines 124-139)
- **Vue Page**: `resources/js/pages/Admin/DatabaseBackups/Index.vue`
- **Sidebar**: `resources/js/components/AppSidebar.vue`
- **Storage**: `storage/app/backups/`

## API Endpoints

All endpoints require System Admin authentication:

- `GET /admin/database-backups` - List backups (Inertia page)
- `POST /admin/database-backups` - Create backup
- `GET /admin/database-backups/{id}/download` - Download backup
- `POST /admin/database-backups/{id}/restore` - Restore database
- `POST /admin/database-backups/{id}/trash` - Move to trash
- `POST /admin/database-backups/{id}/restore-from-trash` - Restore from trash
- `DELETE /admin/database-backups/{id}` - Permanent delete
- `GET /admin/api/database-backups/stats` - Get statistics

## Testing

### Manual Testing Checklist
- [ ] Create backup manually
- [ ] Download backup file
- [ ] View backup in table
- [ ] Filter by status (Active/Trashed)
- [ ] Search backups
- [ ] Sort by different columns
- [ ] Move backup to trash
- [ ] Restore from trash
- [ ] Restore database (test environment only!)
- [ ] Permanently delete backup
- [ ] Run cleanup command
- [ ] Verify scheduled tasks
- [ ] Test as non-System Admin (should be denied)

### Automated Commands
```bash
# Create test backup
php artisan backups:create-scheduled

# Test cleanup (dry run - check output)
php artisan backups:clean --trash-days=0 --delete-days=0

# List scheduled tasks
php artisan schedule:list
```

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review this documentation
3. Verify MySQL connection and permissions
4. Ensure scheduled tasks are running
5. Check file permissions on storage directory

---

**System Status**: ✅ Production Ready
**Last Updated**: October 16, 2025
