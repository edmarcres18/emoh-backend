# 🚨 EMOH API Error Fix Guide

## Critical Issues Identified

### 1. **CORS Configuration Error**
- **Problem**: `Access-Control-Allow-Origin` cannot be wildcard `*` when using `credentials: 'include'`
- **Solution**: Configure specific allowed origins

### 2. **API URL Duplication**
- **Problem**: URLs like `https://emoh.chuweydev.site/api/client/login/client/login` (duplicated path)
- **Solution**: Fixed protocol fallback method

### 3. **Missing CORS Middleware**
- **Problem**: Backend not properly handling CORS requests
- **Solution**: Added CORS middleware to API routes

## ✅ **Fixes Applied**

### 1. **Backend CORS Configuration**
Created `emoh-backend/config/cors.php`:
```php
<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:4013',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:4013',
        'http://192.168.1.210:4013',
        'https://emoh.chuweydev.site',
        'https://www.emoh.chuweydev.site',
        env('FRONTEND_URL', 'http://localhost:4013'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 2. **Sanctum Configuration Update**
Updated `emoh-backend/config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s%s',
    'localhost,localhost:3000,localhost:4013,127.0.0.1,127.0.0.1:8000,127.0.0.1:4013,::1,192.168.1.210:4013',
    env('APP_ENV') === 'production' ? ',admin.chuweyweb.site,www.admin.chuweyweb.site,emoh.chuweydev.site,www.emoh.chuweydev.site' : '',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

### 3. **CORS Middleware Registration**
Updated `emoh-backend/bootstrap/app.php`:
```php
$middleware->api(append: [
    \App\Http\Middleware\ForceJsonResponse::class,
    \Illuminate\Http\Middleware\HandleCors::class,
]);
```

### 4. **Frontend API URL Fix**
Fixed `emoh-frontend/resources/js/services/baseApi.ts`:
```typescript
// Fixed protocol fallback method to prevent URL duplication
for (const baseUrl of this.fallbackURLs) {
  try {
    return await requestFn(baseUrl); // Fixed: was requestFn(fullUrl)
  } catch (error: unknown) {
    // ... error handling
  }
}
```

## 🔧 **Required Backend Configuration**

### 1. **Environment Variables**
Add to your backend `.env` file:
```env
# CORS Configuration
FRONTEND_URL=https://your-frontend-domain.com

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS="emoh.chuweydev.site,www.emoh.chuweydev.site,192.168.1.210:4013"

# Session Configuration
SESSION_DOMAIN=.emoh.chuweydev.site
```

### 2. **Clear Configuration Cache**
Run these commands on your backend:
```bash
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
```

### 3. **Restart Services**
Restart your web server and PHP-FPM:
```bash
# For Laragon
# Restart Apache/Nginx and PHP-FPM

# For Docker
docker-compose restart

# For systemd
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

## 🔧 **Required Frontend Configuration**

### 1. **Environment Variables**
Update your frontend `.env` file:
```env
# Production API Configuration
VITE_BASE_API_URL=https://emoh.chuweydev.site/api
VITE_BACKEND_URL=https://emoh.chuweydev.site

# Disable protocol fallback in production
VITE_ENABLE_PROTOCOL_FALLBACK=false

# Production settings
VITE_APP_ENV=production
VITE_APP_DEBUG=false
```

### 2. **Rebuild Frontend**
```bash
npm run build
# or for development
npm run dev
```

## 🧪 **Testing the Fix**

### 1. **Test CORS**
Open browser console and test:
```javascript
fetch('https://emoh.chuweydev.site/sanctum/csrf-cookie', {
  method: 'GET',
  credentials: 'include',
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
})
.then(response => console.log('CORS OK:', response.status))
.catch(error => console.error('CORS Error:', error));
```

### 2. **Test API Endpoints**
```javascript
fetch('https://emoh.chuweydev.site/api/client/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  body: JSON.stringify({
    email: 'test@example.com',
    password: 'password'
  })
})
.then(response => console.log('API OK:', response.status))
.catch(error => console.error('API Error:', error));
```

## 🚀 **Production Deployment Checklist**

### Backend:
- [ ] CORS configuration file created
- [ ] Sanctum stateful domains updated
- [ ] CORS middleware registered
- [ ] Environment variables set
- [ ] Configuration cache cleared
- [ ] Services restarted

### Frontend:
- [ ] Environment variables updated
- [ ] API URL duplication fixed
- [ ] Frontend rebuilt
- [ ] HTTPS URLs configured

### Testing:
- [ ] CORS preflight requests working
- [ ] CSRF cookie accessible
- [ ] API endpoints responding correctly
- [ ] Authentication flow working
- [ ] No console errors

## 🔍 **Troubleshooting**

### If CORS still fails:
1. Check browser network tab for preflight requests
2. Verify `Access-Control-Allow-Origin` header in response
3. Ensure `supports_credentials` is true
4. Check that origin is in allowed_origins list

### If API URLs are still duplicated:
1. Clear browser cache
2. Rebuild frontend assets
3. Check environment variables
4. Verify protocol fallback is disabled

### If authentication fails:
1. Check Sanctum stateful domains
2. Verify CSRF cookie is being set
3. Check token storage in localStorage
4. Verify API routes are accessible

## 📞 **Support**

If issues persist:
1. Check browser console for specific errors
2. Verify backend logs for server-side errors
3. Test API endpoints directly with Postman/curl
4. Ensure all environment variables are correct

The fixes above should resolve all the CORS and API URL issues you're experiencing.
