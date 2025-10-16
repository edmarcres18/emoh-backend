# Database Backup System - Quick Setup Guide

## Prerequisites

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Laravel 12
- Node.js 18+ (for Vue.js frontend)
- `mysqldump` and `mysql` CLI tools installed

## Installation Steps

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Ensure Queue is Configured

Check your `.env` file:
```env
QUEUE_CONNECTION=database
```

If using database queue, run the migration:
```bash
php artisan queue:table
php artisan migrate
```

### 3. Start Queue Worker (Development)

```bash
php artisan queue:work
```

Keep this running in a separate terminal.

### 4. Setup Cron Job (Production)

Add to crontab:
```bash
* * * * * cd /path/to/emoh-backend && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Compile Frontend Assets

```bash
npm run build
# or for development
npm run dev
```

### 6. Set Permissions

```bash
chmod -R 775 storage/app/backups
chown -R www-data:www-data storage/app/backups
```

## Quick Test

### Create a Test Backup

```bash
php artisan backup:database
```

You should see:
```
Starting database backup...
Creating backup synchronously...
Backup created successfully!
Filename: backup_2024_01_16_123456_abc123def456.sql
File size: 2.5 MB
```

### Verify Backup File

```bash
ls -lh storage/app/backups/
```

### Access Web Interface

1. Login as System Admin
2. Navigate to: `http://your-domain/admin/database-backups`
3. Click "Create Backup" to test the UI

## Scheduled Jobs

The system runs these jobs automatically:

- **00:00 Daily** - Create database backup
- **01:00 Daily** - Move backups older than 15 days to trash
- **02:00 Daily** - Permanently delete trash items older than 7 days

## Production Setup

### Supervisor for Queue Worker

1. Install Supervisor:
```bash
sudo apt-get install supervisor
```

2. Create config file `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/emoh-backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/emoh-backend/storage/logs/worker.log
```

3. Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Verify System is Working

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

### Check Queue Status
```bash
php artisan queue:work --once
```

### Monitor Logs
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Issue: mysqldump not found
```bash
# Install MySQL client tools
sudo apt-get install mysql-client
```

### Issue: Permission denied
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### Issue: Queue jobs not processing
```bash
# Restart queue worker
php artisan queue:restart

# Check for failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Access Control

Only users with the **System Admin** role can:
- View backups
- Create backups
- Download backups
- Restore database
- Manage trash

## Security Notes

- Backup files are stored in `storage/app/backups` (not publicly accessible)
- All operations require System Admin role
- CSRF protection enabled on all mutations
- Database credentials are escaped in shell commands
- Async operations prevent request timeouts

## Next Steps

1. Test backup creation via UI
2. Test downloading a backup
3. Test moving to trash
4. Verify scheduled jobs are running
5. Monitor disk space usage

For detailed documentation, see `DATABASE_BACKUP_DOCUMENTATION.md`
