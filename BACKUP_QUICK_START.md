# Database Backup - Quick Start Guide

## 🚀 Quick Commands

### Create Backup Now
```bash
php artisan backup:database
```

### Create Backup + Cleanup Old Files
```bash
php artisan backup:database --keep-days=30
```

### Clean Old Backups Only
```bash
php artisan backup:cleanup --days=30
```

### List Available Commands
```bash
php artisan list backup
```

### Test Scheduler
```bash
php artisan schedule:list
php artisan schedule:run
```

## 📍 Access Points

### Web Interface
- **URL**: `http://your-domain/admin/database-backup`
- **Required Role**: System Admin or Admin
- **Features**: Create, Download, Delete, Restore backups

### Storage Location
- **Path**: `storage/app/backups/database/`
- **Format**: `emoh_backup_YY_MM_DD_HHMMSS.sql`

## ⏰ Automated Schedule

### Daily Backup
- **Time**: 2:00 AM
- **Retention**: 30 days
- **Command**: `backup:database --keep-days=30`

### Weekly Cleanup
- **Day**: Sunday at 3:00 AM
- **Command**: `backup:cleanup --days=30`

## 🔧 Setup for Production

### 1. Windows Task Scheduler
Create a task to run every minute:
```cmd
php C:\laragon\www\emoh\emoh-backend\artisan schedule:run
```

### 2. Linux Crontab
Add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Verify Setup
```bash
# Check if commands are registered
php artisan list backup

# Should show:
#   backup:cleanup  Clean up old database backup files
#   backup:database Create a database backup and optionally clean old backups
```

## ✅ Testing

### Test Backup Creation
```bash
php artisan backup:database
# Expected: ✓ Backup created successfully: emoh_backup_XX_XX_XX_XXXXXX.sql
```

### Test Cleanup
```bash
php artisan backup:cleanup --days=1
# Expected: Shows deleted files and freed space
```

### Check Backup Files
```bash
# Windows
dir storage\app\backups\database

# Linux
ls -lh storage/app/backups/database/
```

## 🛡️ Security

- Only **System Admin** and **Admin** roles can access backup features
- Backups are stored outside the public directory
- `.gitignore` prevents committing backups to Git

## 📊 Database Support

- ✅ MySQL (with mysqldump or PHP fallback)
- ✅ PostgreSQL (requires pg_dump)
- ✅ SQLite (built-in PHP support)

## 🔍 Troubleshooting

### Check Permissions
```bash
chmod -R 755 storage/app/backups
```

### View Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### Test Database Connection
```bash
php artisan db:show
```

## 📚 Full Documentation
See `DATABASE_BACKUP_GUIDE.md` for complete documentation.
