# Database Backup System

This application includes a comprehensive database backup system that supports MySQL, PostgreSQL, and SQLite databases. The system provides both web-based management and command-line tools for creating, managing, and restoring database backups.

## Features

- **Multi-Database Support**: Works with MySQL, PostgreSQL, and SQLite
- **Automated Backups**: Scheduled daily backups with configurable retention
- **Web Interface**: User-friendly interface for managing backups (Admin only)
- **CLI Commands**: Artisan commands for automation and cron jobs
- **Restore Functionality**: Restore database from any backup file
- **Automatic Cleanup**: Remove old backups based on retention policy
- **Security**: Admin-only access with permission checks
- **Fallback Support**: PHP-based backup when native tools unavailable

## Quick Start

### 1. Manual Backup (CLI)

Create a backup immediately:

```bash
php artisan backup:database
```

Create a backup and clean old files (keep 30 days):

```bash
php artisan backup:database --keep-days=30
```

### 2. Clean Old Backups

Remove backups older than specified days:

```bash
php artisan backup:cleanup --days=30
```

### 3. Web Interface

Access the backup management interface:

```
URL: /admin/database-backup
Role Required: System Admin or Admin
```

## Automated Backups

The system includes pre-configured scheduled tasks in `routes/console.php`:

### Daily Backup
- **Time**: 2:00 AM
- **Retention**: 30 days
- **Command**: `backup:database --keep-days=30`

### Weekly Cleanup
- **Day**: Sunday
- **Time**: 3:00 AM
- **Retention**: 30 days
- **Command**: `backup:cleanup --days=30`

### Running the Scheduler

For the automated backups to work, add this to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or for Windows Task Scheduler, create a task that runs every minute:

```cmd
php C:\laragon\www\emoh\emoh-backend\artisan schedule:run
```

## Backup Storage

Backups are stored in:
```
storage/app/backups/database/
```

Filename format:
```
emoh_backup_YY_MM_DD_HHMMSS.sql
```

Example:
```
emoh_backup_25_01_16_143025.sql
```

## Database Driver Support

### MySQL
- Uses `mysqldump` command (preferred)
- Falls back to PHP-based backup if mysqldump unavailable
- Includes all tables, data, and structure

### PostgreSQL
- Uses `pg_dump` command
- Requires PostgreSQL client tools installed
- Plain SQL format for easy restoration

### SQLite
- PHP-based dump (no external tools required)
- Includes schema and all data
- Preserves foreign key constraints

## Restore Database

### Via Web Interface
1. Navigate to `/admin/database-backup`
2. Select a backup from the list
3. Click "Restore" button
4. Confirm the restoration

### Upload and Restore
1. Navigate to `/admin/database-backup`
2. Click "Upload & Restore"
3. Select a `.sql` file
4. System will restore the database

## Security

### Access Control
- Only users with **System Admin** or **Admin** roles can:
  - View backup management page
  - Create backups
  - Download backups
  - Delete backups
  - Restore backups

### File Validation
- Filename validation prevents directory traversal attacks
- Only `.sql` files are accepted
- File size limit: 100MB for uploads

### Storage Security
- Backups stored outside public directory
- `.gitignore` prevents committing backup files to version control

## Production Deployment

### Requirements

1. **For MySQL**:
   ```bash
   # Verify mysqldump is available
   mysqldump --version
   ```

2. **For PostgreSQL**:
   ```bash
   # Verify pg_dump is available
   pg_dump --version
   ```

3. **Storage Permissions**:
   ```bash
   chmod 755 storage/app/backups/database
   ```

### Environment Configuration

Ensure your `.env` file has correct database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Cron Setup

Add to crontab for Linux/Unix:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at startup, repeat every 1 minute
4. Action: Start a program
5. Program: `php.exe`
6. Arguments: `C:\laragon\www\emoh\emoh-backend\artisan schedule:run`

## API Routes

All routes require authentication and admin privileges:

```php
GET  /admin/database-backup                    # View backup management
POST /admin/database-backup/create             # Create new backup
GET  /admin/database-backup/download/{filename} # Download backup
DELETE /admin/database-backup/{filename}       # Delete backup
POST /admin/database-backup/restore            # Restore from existing backup
POST /admin/database-backup/upload-restore     # Upload and restore
```

## Troubleshooting

### Backup Creation Fails

**Problem**: "mysqldump: command not found"

**Solution**: Install MySQL client tools or the system will automatically use PHP-based backup.

```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# CentOS/RHEL
sudo yum install mysql

# macOS
brew install mysql-client
```

### Permission Denied

**Problem**: Cannot write to backup directory

**Solution**: Fix directory permissions

```bash
chmod -R 755 storage/app/backups
chown -R www-data:www-data storage/app/backups  # Linux
```

### Scheduler Not Running

**Problem**: Automated backups don't execute

**Solution**: Verify cron job is set up

```bash
# Check crontab
crontab -l

# Test scheduler manually
php artisan schedule:run
```

### Large Database Timeout

**Problem**: Backup times out for large databases

**Solution**: The backup timeout is set to 300 seconds (5 minutes). To increase:

Edit `DatabaseBackupController.php`:
```php
$process->setTimeout(600); // 10 minutes
```

## File Structure

```
app/
├── Console/
│   └── Commands/
│       ├── DatabaseBackup.php      # Backup command
│       └── DatabaseCleanup.php     # Cleanup command
└── Http/
    └── Controllers/
        └── DatabaseBackupController.php  # Web controller

routes/
├── console.php                      # Scheduled tasks
└── web.php                          # Web routes

storage/
└── app/
    └── backups/
        └── database/                # Backup storage
            └── .gitignore
```

## Testing

### Test Backup Creation

```bash
php artisan backup:database
```

Expected output:
```
Starting database backup...
✓ Backup created successfully: emoh_backup_25_01_16_143025.sql
```

### Test Cleanup

```bash
php artisan backup:cleanup --days=7
```

Expected output:
```
Starting backup cleanup...
✓ Deleted 3 backup(s) older than 7 days
✓ Freed up 15.2 MB of disk space
```

### Verify Scheduler

```bash
php artisan schedule:list
```

Should show:
```
backup:database --keep-days=30 ............... Daily at 2:00 AM
backup:cleanup --days=30 .................... Weekly on Sunday at 3:00 AM
```

## Best Practices

1. **Regular Testing**: Test restoration process regularly
2. **Off-site Backups**: Copy backups to external storage (S3, etc.)
3. **Monitor Disk Space**: Ensure adequate storage for backups
4. **Retention Policy**: Adjust `--keep-days` based on your needs
5. **Backup Before Updates**: Always backup before major updates
6. **Verify Backups**: Occasionally restore to test database
7. **Document Process**: Keep team informed of backup procedures
8. **Secure Storage**: Restrict access to backup directory
9. **Log Monitoring**: Check logs for backup failures

## Customization

### Change Backup Schedule

Edit `routes/console.php`:

```php
// Backup every 6 hours
Schedule::command('backup:database --keep-days=30')
    ->everySixHours();

// Backup twice daily
Schedule::command('backup:database --keep-days=30')
    ->twiceDaily(1, 13);

// Backup weekly on Monday
Schedule::command('backup:database --keep-days=30')
    ->weeklyOn(1, '02:00');
```

### Change Retention Period

```bash
# Keep backups for 60 days
php artisan backup:database --keep-days=60

# Keep backups for 7 days
php artisan backup:database --keep-days=7
```

### Change Backup Filename

Edit `DatabaseBackupController.php` and `DatabaseBackup.php`:

```php
$filename = 'myapp_backup_' . date('Y-m-d_His') . '.sql';
```

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review Laravel logs: `storage/logs/laravel.log`
3. Contact system administrator

## Version

- **Version**: 1.0.0
- **Laravel**: 12.x
- **PHP**: 8.2+
- **Last Updated**: January 2025
