# Production Configuration Guide

## Environment Variables for Production

Create a `.env` file on your production server with the following configuration:

```bash
# Production Environment Configuration
APP_NAME="EMOH Backend"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://emoh.chuweydev.site

# Force HTTPS in production
FORCE_HTTPS=true

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=emoh_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@emoh.chuweydev.site
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=emoh.chuweydev.site,www.emoh.chuweydev.site,chuweydev.site,www.chuweydev.site,admin.chuweyweb.site,www.admin.chuweyweb.site

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://emoh.chuweydev.site,https://www.emoh.chuweydev.site,https://chuweydev.site,https://www.chuweydev.site,https://admin.chuweyweb.site,https://www.admin.chuweyweb.site

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Session Configuration
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.chuweydev.site
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Cookie Configuration
COOKIE_SECURE=true
COOKIE_HTTP_ONLY=true
COOKIE_SAME_SITE=lax
```

## Deployment Checklist

1. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

2. **Run Database Migrations**
   ```bash
   php artisan migrate --force
   ```

3. **Clear and Cache Configuration**
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Set Proper Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

5. **Check SSL Certificate**
   - Ensure SSL certificate is valid and properly configured
   - Test HTTPS endpoints manually

6. **Test API Endpoints**
   ```bash
   curl -X POST https://emoh.chuweydev.site/api/client/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"email":"test@example.com","password":"password"}'
   ```

## Common Issues and Solutions

### 500 Internal Server Error
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify database connection
3. Check file permissions
4. Verify SSL certificate

### CORS Issues
1. Update `config/cors.php` with correct domains
2. Clear config cache: `php artisan config:cache`
3. Restart web server

### SSL/HTTPS Issues
1. Verify SSL certificate is valid
2. Check if `FORCE_HTTPS=true` is set
3. Ensure all assets use HTTPS URLs

### Database Connection Issues
1. Verify database credentials
2. Check database server is running
3. Test connection: `php artisan tinker` then `DB::connection()->getPdo()`
