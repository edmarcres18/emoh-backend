# Database Backup Management System - Documentation

## Overview

The Database Backup Management System provides a complete solution for creating, managing, restoring, and maintaining MySQL database backups with a modern Vue.js interface and robust Laravel backend.

## Features

### Core Functionalities
- ✅ **Automated Database Backups** - Create full database backups as `.sql` files
- ✅ **Downloadable Backups** - Secure download of backup files with proper authorization
- ✅ **Database Restoration** - Restore database from any completed backup
- ✅ **Trash System** - 15-day auto-trash with 7-day retention before permanent deletion
- ✅ **Scheduled Jobs** - Automated daily backups and cleanup via Laravel Scheduler
- ✅ **Async Operations** - Queue-based backup creation and restoration
- ✅ **Real-time Updates** - Auto-refresh every 30 seconds for live status updates
- ✅ **Role-Based Access** - System Admin only access with Laravel Policies
- ✅ **Comprehensive Statistics** - Track backup counts, sizes, and statuses

## Installation & Setup

### 1. Run Database Migration

```bash
php artisan migrate
```

This creates the `database_backups` table with all required columns and indexes.

### 2. Create Storage Directory

The system stores backups in `storage/app/backups`. This directory will be created automatically, but you can create it manually:

```bash
mkdir -p storage/app/backups
chmod 755 storage/app/backups
```

### 3. Configure Queue Worker

The system uses Laravel Queues for async operations. Ensure your queue worker is running:

```bash
# For development
php artisan queue:work

# For production (using Supervisor)
# See section on Production Deployment
```

### 4. Configure Task Scheduler

Add this to your crontab to enable scheduled backups:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Usage

### Via Web Interface

1. **Access the Module**
   - Navigate to `/admin/database-backups`
   - Only System Admins can access this module

2. **Create a Backup**
   - Click "Create Backup" button
   - Backup is queued and processes asynchronously
   - Status updates automatically every 30 seconds

3. **Download a Backup**
   - Click the download icon on any completed backup
   - File downloads directly as `.sql`

4. **Restore Database**
   - Click the restore icon on a completed backup
   - Confirm the restoration (this replaces your current database)
   - Process runs asynchronously in the background

5. **Trash Management**
   - Switch to "Trash" tab to view trashed backups
   - Backups older than 15 days automatically move to trash
   - Restore from trash or permanently delete

### Via Artisan Commands

#### Create a Backup

```bash
# Create backup synchronously (waits for completion)
php artisan backup:database

# Queue backup (runs in background)
php artisan backup:database --queue
```

#### Manual Trash Processing

```bash
# Move backups older than 15 days to trash
php artisan backup:auto-trash
```

#### Manual Trash Cleanup

```bash
# Permanently delete trash items older than 7 days
php artisan backup:cleanup-trash
```

## Scheduled Jobs

The system automatically runs these jobs daily:

| Time | Command | Description |
|------|---------|-------------|
| 00:00 | `backup:database --queue` | Creates daily database backup |
| 01:00 | `backup:auto-trash` | Moves old backups (>15 days) to trash |
| 02:00 | `backup:cleanup-trash` | Permanently deletes trash items (>7 days) |

## API Endpoints

All endpoints require System Admin role authentication.

### List Backups
```
GET /admin/api/database-backups
Query Parameters:
  - page: int (pagination)
  - search: string (search filename/identifier)
  - sort: string (latest|oldest|name_asc|name_desc|size_asc|size_desc)
  - status: string (completed|pending|in_progress|failed)
  - view: string (active|trash)
```

### Create Backup
```
POST /admin/api/database-backups
Response: { success: true, data: BackupObject }
```

### Get Backup Details
```
GET /admin/api/database-backups/{id}
Response: { success: true, data: BackupObject }
```

### Download Backup
```
GET /admin/database-backups/{id}/download
Response: File download (application/sql)
```

### Restore Database
```
POST /admin/api/database-backups/{id}/restore
Response: { success: true, message: "Database restore queued..." }
```

### Move to Trash
```
POST /admin/api/database-backups/{id}/trash
Response: { success: true, message: "Backup moved to trash" }
```

### Restore from Trash
```
POST /admin/api/database-backups/{id}/restore-from-trash
Response: { success: true, message: "Backup restored from trash" }
```

### Delete Permanently
```
DELETE /admin/api/database-backups/{id}
Response: { success: true, message: "Backup deleted permanently" }
```

### Get Statistics
```
GET /admin/api/database-backups/statistics
Response: { 
  success: true, 
  data: {
    total_backups, completed_backups, failed_backups, 
    trashed_backups, total_size, average_size, 
    latest_backup, oldest_backup
  }
}
```

### Bulk Operations
```
DELETE /admin/api/database-backups/bulk-delete
POST /admin/api/database-backups/bulk-trash
Body: { backup_ids: [1, 2, 3] }
```

## Database Schema

### `database_backups` Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| filename | string | Backup filename |
| unique_identifier | string | 32-char unique ID |
| path | string | Storage path |
| file_size | bigint | Size in bytes |
| status | enum | pending, in_progress, completed, failed, in_trash |
| error_message | text | Error details if failed |
| backup_date | timestamp | When backup was created |
| trashed_at | timestamp | When moved to trash |
| completed_at | timestamp | When backup completed |
| created_by | foreignId | User who created (nullable) |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Record update time |

**Indexes:**
- `status`
- `backup_date`
- `trashed_at`
- `(status, backup_date)` composite

## Architecture

### Backend Components

1. **Model**: `App\Models\DatabaseBackup`
   - Eloquent model with scopes and helper methods
   - Relationships to User model

2. **Service**: `App\Services\DatabaseBackupService`
   - Core business logic
   - Handles backup creation, restoration, trash management
   - Uses `mysqldump` and `mysql` CLI tools

3. **Controller**: `App\Http\Controllers\Admin\DatabaseBackupController`
   - RESTful API endpoints
   - Authorization via policies
   - Returns JSON responses

4. **Policy**: `App\Policies\DatabaseBackupPolicy`
   - System Admin only access
   - Granular permissions per action

5. **Jobs**:
   - `App\Jobs\CreateDatabaseBackupJob` - Async backup creation
   - `App\Jobs\RestoreDatabaseBackupJob` - Async database restoration

6. **Commands**:
   - `App\Console\Commands\CreateDatabaseBackup`
   - `App\Console\Commands\ProcessBackupAutoTrash`
   - `App\Console\Commands\ProcessBackupPermanentDeletion`

### Frontend Components

**Vue Component**: `resources/js/pages/Admin/DatabaseBackups/Index.vue`
- Composition API with TypeScript
- Axios for API calls
- Auto-refresh every 30 seconds
- Responsive design matching Properties pages
- Sortable, filterable, searchable interface

## Security

- **Authorization**: Laravel Policies restrict access to System Admins only
- **CSRF Protection**: All POST/PUT/DELETE requests include CSRF tokens
- **File Security**: Backups stored in non-public directory (`storage/app/backups`)
- **Download Validation**: Authorization check before file download
- **SQL Injection**: Uses Laravel query builder and prepared statements
- **Command Injection**: Uses `escapeshellarg()` for all shell parameters

## Error Handling

### Backup Creation Errors
- Invalid database credentials → Failed status with error message
- Insufficient disk space → Failed status
- MySQL/mysqldump not available → Failed status
- Empty backup file → Failed status, file deleted

### Restoration Errors
- Backup file not found → Error response
- Invalid SQL syntax → Error logged, transaction rolled back
- Permission issues → Error response

### All errors are logged to Laravel logs**:
```
storage/logs/laravel.log
```

## Performance Considerations

### Optimization Strategies
1. **Async Operations**: Backups/restores run in queue to prevent blocking
2. **Database Indexes**: Multiple indexes on frequently queried columns
3. **Pagination**: Large backup lists paginated (10 per page)
4. **Compression**: Consider adding gzip compression for large databases
5. **Cleanup Jobs**: Automatic deletion prevents disk space issues

### Production Recommendations
- Monitor disk space regularly
- Set up queue workers with Supervisor
- Configure queue failure notifications
- Consider offsite backup storage (S3, etc.)
- Test restore procedures regularly

## Production Deployment

### Supervisor Configuration

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasec=10
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/worker.log
stopwaitsecs=3600
```

Then reload Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Cron Configuration

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Environment Variables

Ensure these are set in `.env`:
```env
QUEUE_CONNECTION=database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Testing

### Manual Testing Checklist

- [ ] Create backup manually via UI
- [ ] Verify backup file exists in storage/app/backups
- [ ] Download backup file
- [ ] Restore from backup
- [ ] Verify data restored correctly
- [ ] Move backup to trash
- [ ] Restore from trash
- [ ] Delete backup permanently
- [ ] Verify scheduled jobs run correctly
- [ ] Test with failed database credentials
- [ ] Test with large database (>100MB)

### Monitoring

Check backup system health:
```bash
# View recent backups
php artisan tinker
>>> App\Models\DatabaseBackup::latest()->take(5)->get(['id', 'filename', 'status', 'file_size', 'backup_date'])

# Check queue status
php artisan queue:failed

# View logs
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Backup Creation Fails
```bash
# Check if mysqldump is available
which mysqldump

# Test mysqldump manually
mysqldump -u username -p database_name > test.sql

# Check queue worker is running
ps aux | grep queue:work
```

### Restoration Fails
```bash
# Test mysql restore manually
mysql -u username -p database_name < backup_file.sql

# Check file permissions
ls -la storage/app/backups/
```

### Scheduled Jobs Not Running
```bash
# Verify crontab
crontab -l

# Check scheduler log
php artisan schedule:list

# Test scheduler manually
php artisan schedule:run
```

### Queue Jobs Not Processing
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart queue worker
php artisan queue:restart
```

## Maintenance

### Regular Tasks
- **Weekly**: Review backup statistics and disk usage
- **Monthly**: Test database restoration procedure
- **Quarterly**: Review and archive old backups offsite
- **Yearly**: Audit security and update dependencies

### Disk Space Management

Monitor backup directory size:
```bash
du -sh storage/app/backups/
```

Adjust retention periods in `app/Console/Kernel.php` if needed.

## Future Enhancements

Potential improvements:
- [ ] Compression support (gzip)
- [ ] Cloud storage integration (S3, Google Cloud)
- [ ] Email notifications for backup failures
- [ ] Incremental backups
- [ ] Backup encryption
- [ ] Multi-database support
- [ ] Backup scheduling per user preferences
- [ ] Export backup history as CSV/PDF

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Review error messages in UI
3. Verify system requirements (mysqldump, mysql CLI)
4. Test database connectivity
5. Check file permissions

## License

This module is part of the EMOH property management system.

---

**Created**: January 2024  
**Last Updated**: January 2024  
**Version**: 1.0.0
