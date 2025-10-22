# Session Timeout Implementation

## Overview
This feature implements automatic session timeout for client authentication after 1 day (24 hours) of inactivity. When a client's session expires, they will see a prominent alert and be redirected to the login page.

## Backend Implementation

### 1. Database Migration
**File:** `database/migrations/2025_10_22_003328_add_last_activity_to_clients_table.php`
- Adds `last_activity` timestamp column to track user activity
- Run migration: `php artisan migrate`

### 2. Client Model Updates
**File:** `app/Models/Client.php`

**New Methods:**
- `hasSessionTimedOut()` - Checks if session has expired (24 hours of inactivity)
- `updateLastActivity()` - Updates the last activity timestamp
- `getHoursSinceLastActivity()` - Returns hours since last activity

**Modified Methods:**
- `recordSuccessfulLogin()` - Now initializes `last_activity` on login

### 3. Middleware Integration
**File:** `app/Http/Middleware/ClientAuth.php`

The middleware now:
- Checks for session timeout on every authenticated request
- Returns 440 status code with `SESSION_TIMEOUT` error code when session expires
- Automatically updates `last_activity` on each request
- Deletes the expired token to force re-authentication

### 4. Controller Updates
**File:** `app/Http/Controllers/Api/ClientAuthController.php`

- Registration now initializes `last_activity`
- Login updates `last_activity` via `recordSuccessfulLogin()`

## Frontend Implementation

### 1. Toast Notification System
**Files:**
- `resources/js/components/ui/toast.tsx` - Toast component with variants
- `resources/js/services/toastService.ts` - Centralized toast management
- `resources/js/providers/ToastProvider.tsx` - App-wide toast provider

### 2. Session Timeout Modal Alert
**File:** `resources/js/components/SessionTimeoutAlert.tsx`
- Prominent modal dialog for session timeouts
- Includes backdrop, icon, message, and action button
- Auto-redirects to login on confirmation

### 3. API Service Integration
**File:** `resources/js/services/baseApi.ts`

**New Method:**
- `handleSessionTimeout()` - Handles 440 status code and `SESSION_TIMEOUT` error code
  - Clears authentication token
  - Shows modal alert
  - Shows toast notification as fallback
  - Redirects to login page

**Updated:**
- `handleError()` - Now detects session timeout (440 status or SESSION_TIMEOUT error code)
- Added `error_code` to ApiError type

### 4. Type Updates
**File:** `resources/js/types/api.ts`
- Added `error_code` field to `ApiError` interface

### 5. Configuration Updates
**File:** `resources/js/config/api.ts`
- Added `SESSION_TIMEOUT: 440` to HTTP_STATUS constants

### 6. App Integration
**File:** `resources/js/app.tsx`
- Wrapped app with `ToastProvider` for global toast notifications

## How It Works

### Flow Diagram
```
1. Client logs in → last_activity is set
2. Client makes API requests → last_activity is updated
3. Client inactive for 24 hours
4. Next API request → Middleware detects timeout
5. Backend returns 440 status with SESSION_TIMEOUT error code
6. Frontend shows modal alert + toast notification
7. Auth token cleared from localStorage
8. User redirected to login page
```

### Backend Flow
```
ClientAuth Middleware
    ↓
Check if authenticated
    ↓
Check hasSessionTimedOut()
    ↓
If timed out:
    - Delete current token
    - Return 440 response with SESSION_TIMEOUT error
    ↓
If active:
    - Update last_activity
    - Continue request
```

### Frontend Flow
```
API Request Error
    ↓
BaseApiService.handleError()
    ↓
Detect 440 status or SESSION_TIMEOUT error code
    ↓
handleSessionTimeout()
    ↓
    - Clear localStorage token
    - Show modal alert (primary)
    - Show toast notification (fallback)
    - Redirect to /login
```

## Configuration

### Timeout Duration
To change the session timeout duration, modify `Client.php`:

```php
// Current: 24 hours (1 day)
public function hasSessionTimedOut(): bool
{
    if (!$this->last_activity) {
        return false;
    }
    return $this->last_activity->addDay()->isPast();
}

// Example: 2 hours
return $this->last_activity->addHours(2)->isPast();

// Example: 30 minutes
return $this->last_activity->addMinutes(30)->isPast();
```

### Toast Duration
To change toast display duration, modify `toastService.ts`:

```typescript
// Default: 5000ms (5 seconds)
const timer = setTimeout(() => {
  handleClose();
}, duration);

// For session timeout: 6000ms (6 seconds)
toastService.error(timeoutMessage, 6000);
```

## Testing

### Test Session Timeout
1. Login as a client
2. In database, manually update `last_activity` to 25 hours ago:
   ```sql
   UPDATE clients 
   SET last_activity = DATE_SUB(NOW(), INTERVAL 25 HOUR) 
   WHERE email = 'test@example.com';
   ```
3. Make any authenticated API request
4. Should see session timeout modal and be redirected to login

### Test Activity Tracking
1. Login as a client
2. Check `last_activity` in database
3. Make API requests and verify `last_activity` updates

## Security Considerations

- ✅ Tokens are deleted on session timeout (server-side)
- ✅ Client-side tokens are cleared from localStorage
- ✅ Activity is tracked on every authenticated request
- ✅ Timeout is checked before processing any authenticated request
- ✅ No sensitive data is exposed in timeout messages

## Browser Support
- Modern browsers with ES6+ support
- React 18+
- Fetch API support

## Dependencies
- Laravel 11+ (backend)
- React 18+ (frontend)
- TypeScript (frontend)
- Tailwind CSS (styling)

## Troubleshooting

### Modal doesn't show
- Check browser console for errors
- Verify ToastProvider is wrapping the app
- Check that baseApi.ts is handling 440 status codes

### Activity not updating
- Verify middleware is applied to routes
- Check Client model has `last_activity` in fillable array
- Ensure migration has been run

### Redirect not working
- Check login route is accessible at `/login`
- Verify window.location.href is not blocked
- Check browser console for errors
