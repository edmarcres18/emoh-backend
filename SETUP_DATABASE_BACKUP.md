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

### 2. Setup Cron Job (Production)

Add to crontab:
```bash
* * * * * cd /path/to/emoh-backend && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Compile Frontend Assets

```bash
npm run build
# or for development
npm run dev
```

### 4. Set Permissions

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

- **00:00 Daily** - Create database backup (runs synchronously in background)
- **01:00 Daily** - Move backups older than 15 days to trash
- **02:00 Daily** - Permanently delete trash items older than 7 days

## Verify System is Working

### Check Scheduled Tasks
```bash
php artisan schedule:list
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
- Operations run synchronously but scheduled jobs run in background

## Next Steps

1. Test backup creation via UI
2. Test downloading a backup
3. Test moving to trash
4. Verify scheduled jobs are running
5. Monitor disk space usage

For detailed documentation, see `DATABASE_BACKUP_DOCUMENTATION.md`
