# EMOH Backend - Production Deployment Guide

This guide addresses all the production errors and provides step-by-step instructions for a successful deployment.

## Prerequisites

- Docker and Docker Compose installed
- Node.js and npm installed (for frontend build)
- Git installed

## Step-by-Step Deployment

### 1. Environment Setup

1. **Copy environment file:**
   ```bash
   cp env.prod .env
   ```

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

3. **Install PHP dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

### 2. Database Setup

1. **Run database migrations:**
   ```bash
   php artisan migrate --force
   ```

2. **Seed the database (if needed):**
   ```bash
   php artisan db:seed --force
   ```

### 3. Frontend Build

1. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

2. **Build frontend assets:**
   ```bash
   npm run build
   ```

### 4. Docker Deployment

1. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Verify containers are running:**
   ```bash
   docker-compose ps
   ```

### 5. Post-Deployment Setup

1. **Set proper permissions:**
   ```bash
   docker-compose exec app chmod -R 777 storage bootstrap/cache
   ```

2. **Create backup directory:**
   ```bash
   docker-compose exec app mkdir -p storage/app/backups/database
   docker-compose exec app chmod -R 777 storage/app/backups
   ```

3. **Clear application cache:**
   ```bash
   docker-compose exec app php artisan cache:clear
   docker-compose exec app php artisan config:clear
   docker-compose exec app php artisan route:clear
   docker-compose exec app php artisan view:clear
   ```

4. **Test database backup functionality:**
   ```bash
   docker-compose exec app php artisan backup:database
   ```

## Troubleshooting Common Issues

### Issue 1: "No application encryption key has been specified"
**Solution:** Run `php artisan key:generate` before starting the application.

### Issue 2: "Call to undefined method hasAdminPrivileges()"
**Solution:** The method has been added to the User model. Ensure you're using the updated code.

### Issue 3: "Permission denied" for directory creation
**Solution:** 
- Ensure the backup directory exists: `mkdir -p storage/app/backups/database`
- Set proper permissions: `chmod -R 777 storage`

### Issue 4: "Vite manifest not found"
**Solution:** Run `npm run build` to generate the frontend assets.

### Issue 5: "Table 'database_backups' doesn't exist"
**Solution:** Run `php artisan migrate` to create the required tables.

### Issue 6: "mysqldump command not found"
**Solution:** The Dockerfile has been updated to include `mysql-client` and `postgresql-client` packages.

### Issue 7: "Target class DatabaseBackupController does not exist"
**Solution:** The controller has been moved to the `App\Http\Controllers\Admin` namespace.

## Production Environment Variables

Ensure your `.env` file contains:

```env
APP_NAME=EMOH
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=emoh
DB_USERNAME=emoh_user
DB_PASSWORD=your-secure-password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Security Considerations

1. **Change default passwords** in the `.env` file
2. **Use strong database passwords**
3. **Enable HTTPS** in production
4. **Regular security updates** for Docker images
5. **Backup your database regularly** using the built-in backup system

## Monitoring

1. **Check container logs:**
   ```bash
   docker-compose logs -f app
   ```

2. **Monitor database backups:**
   - Access `/admin/database-backup` in your application
   - Check scheduled backups in the dashboard

3. **Verify scheduler is running:**
   ```bash
   docker-compose exec app php artisan schedule:list
   ```

## Backup and Recovery

The system includes automated database backups:
- **Daily backups** at 2:00 AM
- **Weekly cleanup** of old backups (30 days retention)
- **Monthly deep cleanup** (90 days retention)

Manual backup creation:
```bash
docker-compose exec app php artisan backup:database
```

## Support

If you encounter any issues not covered in this guide:
1. Check the application logs: `docker-compose logs app`
2. Verify all environment variables are set correctly
3. Ensure all containers are running: `docker-compose ps`
4. Test database connectivity: `docker-compose exec app php artisan tinker`
