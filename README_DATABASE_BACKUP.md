# 🗄️ Database Backup Management System

A comprehensive, production-ready database backup solution for the EMOH Property Management System, built with Laravel 12 and Vue.js 3.

## 📋 Quick Links

- **[Setup Guide](SETUP_DATABASE_BACKUP.md)** - Quick installation instructions
- **[Full Documentation](DATABASE_BACKUP_DOCUMENTATION.md)** - Complete system documentation
- **[Implementation Checklist](DATABASE_BACKUP_CHECKLIST.md)** - Detailed component list

## ✨ Features

### Core Functionality
- ✅ **One-Click Backup Creation** - Create full MySQL database backups instantly
- ✅ **Secure Downloads** - Download backup .sql files with authorization
- ✅ **Database Restoration** - Restore your database from any backup point
- ✅ **Smart Trash System** - Auto-move backups older than 15 days to trash
- ✅ **Auto-Cleanup** - Permanently delete trash items after 7 days
- ✅ **Scheduled Backups** - Daily automated backups at midnight
- ✅ **Async Processing** - Non-blocking queue-based operations
- ✅ **Real-time Updates** - Auto-refresh every 30 seconds
- ✅ **Statistics Dashboard** - Track backup metrics and storage usage

### Technical Features
- ✅ **Role-Based Access** - System Admin only (Laravel Policies)
- ✅ **Queue Jobs** - Async backup creation and restoration
- ✅ **Artisan Commands** - CLI tools for automation
- ✅ **Comprehensive Logging** - Track all operations
- ✅ **Error Handling** - Graceful failure recovery
- ✅ **Responsive UI** - Works on desktop, tablet, and mobile
- ✅ **Search & Filter** - Find backups quickly
- ✅ **Bulk Operations** - Manage multiple backups at once

## 🚀 Quick Start

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Start Queue Worker
```bash
php artisan queue:work
```

### 3. Setup Cron (Production)
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Access UI
Navigate to: `http://your-domain/admin/database-backups`

## 📂 File Structure

```
app/
├── Console/Commands/
│   ├── CreateDatabaseBackup.php
│   ├── ProcessBackupAutoTrash.php
│   └── ProcessBackupPermanentDeletion.php
├── Http/Controllers/Admin/
│   └── DatabaseBackupController.php
├── Jobs/
│   ├── CreateDatabaseBackupJob.php
│   └── RestoreDatabaseBackupJob.php
├── Models/
│   └── DatabaseBackup.php
├── Policies/
│   └── DatabaseBackupPolicy.php
└── Services/
    └── DatabaseBackupService.php

database/migrations/
└── 2024_10_16_061041_create_database_backups_table.php

resources/js/pages/Admin/DatabaseBackups/
└── Index.vue

routes/
└── web.php (updated with backup routes)
```

## 🎯 Usage Examples

### Create Backup (CLI)
```bash
# Synchronous
php artisan backup:database

# Async (queued)
php artisan backup:database --queue
```

### Create Backup (API)
```javascript
// POST /admin/api/database-backups
const response = await axios.post('/admin/api/database-backups');
```

### List Backups
```javascript
// GET /admin/api/database-backups?view=active&sort=latest
const response = await axios.get('/admin/api/database-backups', {
  params: { view: 'active', sort: 'latest' }
});
```

### Download Backup
```javascript
// Direct download
window.location.href = `/admin/database-backups/${backupId}/download`;
```

### Restore Database
```javascript
// POST /admin/api/database-backups/{id}/restore
const response = await axios.post(`/admin/api/database-backups/${backupId}/restore`);
```

## 📊 Database Schema

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| filename | string | backup_YYYY_MM_DD_HHmmss_uniqueid.sql |
| unique_identifier | string | 16-char unique ID |
| path | string | backups/filename.sql |
| file_size | bigint | Size in bytes |
| status | enum | pending, in_progress, completed, failed, in_trash |
| error_message | text | Error details if failed |
| backup_date | timestamp | Creation time |
| trashed_at | timestamp | When moved to trash |
| completed_at | timestamp | When completed |
| created_by | foreignId | User who created (nullable) |

## ⏰ Scheduled Jobs

| Time | Command | Action |
|------|---------|--------|
| 00:00 | `backup:database --queue` | Daily backup |
| 01:00 | `backup:auto-trash` | Move old backups to trash (>15 days) |
| 02:00 | `backup:cleanup-trash` | Delete trash items (>7 days) |

## 🔒 Security

- **Authorization**: System Admin role required for all operations
- **CSRF Protection**: All mutations protected
- **File Access**: Backups stored in non-public directory (`storage/app/backups`)
- **SQL Injection**: Prevented via Laravel query builder
- **Command Injection**: All shell parameters escaped with `escapeshellarg()`
- **Download Security**: Authorization check before file access

## 🎨 UI Features

### Main Interface
- **Statistics Cards** - Total, Completed, Trash, Size metrics
- **View Modes** - Toggle between Active and Trash
- **Search** - Filter by filename, ID, or creator
- **Filters** - Status, date range, sorting options
- **Actions** - Download, Restore, Trash, Delete

### Real-time Updates
- Auto-refresh every 30 seconds
- Loading states for async operations
- Success/error toast notifications
- Confirmation modals for destructive actions

## 📝 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/api/database-backups` | List backups |
| POST | `/admin/api/database-backups` | Create backup |
| GET | `/admin/api/database-backups/statistics` | Get stats |
| GET | `/admin/api/database-backups/{id}` | Show details |
| GET | `/admin/database-backups/{id}/download` | Download file |
| POST | `/admin/api/database-backups/{id}/restore` | Restore DB |
| POST | `/admin/api/database-backups/{id}/trash` | Move to trash |
| POST | `/admin/api/database-backups/{id}/restore-from-trash` | Restore from trash |
| DELETE | `/admin/api/database-backups/{id}` | Delete permanently |

## 🛠️ Troubleshooting

### Backup Creation Fails
```bash
# Check mysqldump is available
which mysqldump

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Queue Not Processing
```bash
# Check queue worker
ps aux | grep queue:work

# Restart queue
php artisan queue:restart
```

### Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage/app/backups
chown -R www-data:www-data storage/
```

## 📈 Monitoring

### Check System Health
```bash
# View recent backups
php artisan tinker
>>> App\Models\DatabaseBackup::latest()->take(5)->get()

# Check failed jobs
php artisan queue:failed

# Monitor logs
tail -f storage/logs/laravel.log
```

## 🚀 Production Deployment

### Supervisor Setup
```ini
[program:laravel-worker]
command=php /var/www/emoh-backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

### Cron Setup
```bash
* * * * * cd /var/www/emoh-backend && php artisan schedule:run >> /dev/null 2>&1
```

## 📦 Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Laravel 12
- Node.js 18+ (for frontend)
- `mysqldump` and `mysql` CLI tools
- Queue worker (Supervisor in production)

## 🎓 Best Practices

1. **Regular Testing** - Test restore procedures monthly
2. **Offsite Backups** - Archive important backups to external storage
3. **Monitor Disk Space** - Set up alerts for low disk space
4. **Review Logs** - Check logs weekly for errors
5. **Update Retention** - Adjust cleanup schedules based on needs
6. **Security Audits** - Regular permission and access reviews

## 📞 Support

### Documentation Files
- `SETUP_DATABASE_BACKUP.md` - Installation guide
- `DATABASE_BACKUP_DOCUMENTATION.md` - Complete documentation
- `DATABASE_BACKUP_CHECKLIST.md` - Implementation checklist

### Logs
- Laravel: `storage/logs/laravel.log`
- Queue: `storage/logs/worker.log`

### Commands
```bash
# Test backup
php artisan backup:database

# View schedule
php artisan schedule:list

# Check routes
php artisan route:list | grep backup
```

## 🎉 Credits

Built for the EMOH Property Management System using:
- Laravel 12 (Backend)
- Vue.js 3 (Frontend)
- Tailwind CSS (Styling)
- Axios (HTTP client)
- MySQL (Database)

## 📄 License

Part of the EMOH Property Management System.

---

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Status**: Production Ready ✅
