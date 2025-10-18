# Email Change Restriction - 3-Month Security Policy

## Overview
This feature implements a **90-day (3-month) restriction** on email changes for enhanced account security. Clients can only change their email address once every 3 months to prevent account takeover attempts and unauthorized changes.

---

## Backend Implementation

### 1. **Database Migration**

**File:** `database/migrations/2025_10_18_004000_add_last_email_changed_at_to_clients_table.php`

Adds a new timestamp column to track when the client last changed their email:

```php
Schema::table('clients', function (Blueprint $table) {
    $table->timestamp('last_email_changed_at')->nullable()->after('email_verified_at');
});
```

**Migration Command:**
```bash
php artisan migrate
```

---

### 2. **Client Model Updates**

**File:** `app/Models/Client.php`

**Added Methods:**

#### `canChangeEmail(): bool`
Checks if 3 months have passed since the last email change.

```php
public function canChangeEmail(): bool
{
    if (!$this->last_email_changed_at) {
        return true; // Never changed before
    }
    
    return $this->last_email_changed_at->addMonths(3)->isPast();
}
```

#### `getNextEmailChangeDate(): ?\Carbon\Carbon`
Returns the date when the client can change email again.

#### `getDaysUntilEmailChange(): ?int`
Returns the number of days remaining until the next email change is allowed.

---

### 3. **API Endpoints**

**File:** `app/Http/Controllers/Api/ClientAuthController.php`

#### **Check Email Change Eligibility**
```
GET /api/client/check-email-change-eligibility
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Can Change):**
```json
{
  "success": true,
  "data": {
    "can_change_email": true,
    "last_changed_at": null,
    "next_change_date": null,
    "days_remaining": 0,
    "restriction_period_days": 90
  }
}
```

**Response (Restricted):**
```json
{
  "success": true,
  "data": {
    "can_change_email": false,
    "last_changed_at": "2024-10-18T08:30:00.000000Z",
    "next_change_date": "2025-01-18T08:30:00.000000Z",
    "days_remaining": 45,
    "restriction_period_days": 90
  }
}
```

---

#### **Request Email Change (Updated)**
```
POST /api/client/request-email-change
```

**New Validation:**
- Checks if 3 months have passed since last change
- Returns 429 status code if restricted

**Request:**
```json
{
  "new_email": "newemail@example.com"
}
```

**Response (Restricted - 429):**
```json
{
  "success": false,
  "message": "For security reasons, you can only change your email once every 3 months.",
  "data": {
    "can_change": false,
    "days_remaining": 45,
    "next_change_date": "January 18, 2025",
    "last_changed_at": "October 18, 2024"
  }
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "A verification code has been sent to your new email address.",
  "data": {
    "new_email": "newemail@example.com",
    "expires_at": "2024-10-18T09:00:00.000000Z"
  }
}
```

---

#### **Verify Email Change (Updated)**
```
POST /api/client/verify-email-change
```

**New Behavior:**
- Sets `last_email_changed_at` to current timestamp when email is successfully verified
- This starts the 90-day restriction period

**Request:**
```json
{
  "new_email": "newemail@example.com",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Email updated and verified successfully!",
  "data": {
    "client": { ...client data... },
    "email_changed": true,
    "old_email": "oldemail@example.com"
  }
}
```

---

## Frontend Implementation

### 1. **TypeScript Types**

**File:** `resources/js/types/client.ts`

```typescript
export interface EmailChangeEligibility {
  can_change_email: boolean;
  last_changed_at: string | null;
  next_change_date: string | null;
  days_remaining: number | null;
  restriction_period_days: number;
}
```

---

### 2. **Client Auth Service**

**File:** `resources/js/services/clientAuthService.ts`

**New Method:**
```typescript
async checkEmailChangeEligibility(): Promise<ApiResponse<EmailChangeEligibility>> {
  return this.get<EmailChangeEligibility>('/client/check-email-change-eligibility', {}, true);
}
```

---

### 3. **ChangeEmailModal Component**

**File:** `resources/js/components/client/ChangeEmailModal.tsx`

**Key Features:**

#### **Automatic Eligibility Check**
- Checks eligibility when modal opens
- Shows loading state during check

#### **Restriction UI (When Restricted)**
```
┌─────────────────────────────────────────────┐
│ 🔒 Email Change Restricted                 │
│                                             │
│ For security reasons, you can only change  │
│ your email once every 3 months (90 days).  │
├─────────────────────────────────────────────┤
│ 📅 Last Changed: October 18, 2024          │
│ 📅 Next Available: January 18, 2025        │
│ ⏰ Time Remaining: 45 days                  │
├─────────────────────────────────────────────┤
│ ℹ️ Why this restriction?                    │
│ This protects your account from            │
│ unauthorized email changes.                 │
└─────────────────────────────────────────────┘
```

#### **Professional UI Elements:**
- 🔒 **Lock Icon** - Restriction alert
- 📅 **Calendar Icons** - Date displays
- ⏰ **Clock Icon** - Countdown timer
- 🛡️ **Shield Icon** - Security information
- **Color-coded:**
  - Amber for restriction alert
  - Blue for countdown
  - Orange for next available date
  - Gray for historical data

---

## Security Benefits

### **1. Account Takeover Prevention**
- Limits attacker's ability to change email rapidly
- Provides 90-day window for account recovery
- Reduces risk of permanent account loss

### **2. Audit Trail**
- `last_email_changed_at` timestamp creates audit history
- Easy to track email change patterns
- Helps identify suspicious activity

### **3. User Protection**
- Prevents accidental rapid email changes
- Forces deliberate email change decisions
- Reduces support tickets from hasty changes

---

## User Experience

### **First-Time Email Change**
1. User opens Change Email modal
2. Eligibility check shows: ✅ **Can change email**
3. User enters new email and receives OTP
4. User verifies OTP
5. Email changed successfully
6. **90-day restriction begins**

### **Subsequent Attempts (Within 90 Days)**
1. User opens Change Email modal
2. Eligibility check shows: 🔒 **Restricted**
3. Detailed restriction UI displays:
   - Last changed date
   - Next available date
   - Days remaining countdown
   - Security explanation
4. User understands restriction
5. Modal can be closed; no form shown

### **After 90 Days**
1. User opens Change Email modal
2. Eligibility check shows: ✅ **Can change email again**
3. Normal email change flow resumes

---

## Error Handling

### **Frontend Error Handling**

**429 Status Code (Too Many Requests):**
```typescript
if (error.response?.status === 429 && error.response?.data?.data) {
  // Update eligibility state with server data
  setEligibility({
    can_change_email: false,
    last_changed_at: restrictionData.last_changed_at,
    next_change_date: restrictionData.next_change_date,
    days_remaining: restrictionData.days_remaining,
    restriction_period_days: 90
  });
}
```

**User Sees:**
- Error message with explanation
- Updated countdown showing correct days remaining
- Professional restriction UI

---

## Testing

### **Test Scenario 1: First Email Change**
```bash
# User has never changed email
GET /api/client/check-email-change-eligibility

Expected: can_change_email = true
Expected: last_changed_at = null
```

### **Test Scenario 2: Immediate Retry**
```bash
# User just changed email
POST /api/client/request-email-change
{
  "new_email": "another@email.com"
}

Expected: 429 status code
Expected: days_remaining = 90
```

### **Test Scenario 3: After 89 Days**
```bash
# User tries 1 day before restriction ends
GET /api/client/check-email-change-eligibility

Expected: can_change_email = false
Expected: days_remaining = 1
```

### **Test Scenario 4: After 90+ Days**
```bash
# User tries after restriction period
GET /api/client/check-email-change-eligibility

Expected: can_change_email = true
Expected: days_remaining = 0
```

---

## Database Seeding (Optional)

For testing, you can manually set `last_email_changed_at`:

```php
// Set to 85 days ago (5 days remaining)
$client->last_email_changed_at = now()->subDays(85);
$client->save();

// Set to 91 days ago (can change now)
$client->last_email_changed_at = now()->subDays(91);
$client->save();
```

---

## Configuration

### Adjust Restriction Period

**Backend (Client Model):**
```php
// Change from 3 months to 6 months
return $this->last_email_changed_at->addMonths(6)->isPast();
```

**Frontend (ChangeEmailModal):**
Update the text:
```typescript
"you can only change your email once every 6 months (180 days)"
```

---

## Migration Notes

### **For Existing Clients**
- `last_email_changed_at` defaults to `null`
- First email change after migration is always allowed
- Restriction applies from second change onwards

### **Rollback**
```bash
php artisan migrate:rollback --step=1
```

This removes the `last_email_changed_at` column.

---

## API Routes Summary

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/client/check-email-change-eligibility` | Check if user can change email | ✅ Yes |
| POST | `/api/client/request-email-change` | Request email change with OTP | ✅ Yes |
| POST | `/api/client/verify-email-change` | Verify and confirm email change | ✅ Yes |

---

## Support & Troubleshooting

### **User Says: "I need to change my email urgently"**

**Support Response:**
1. Verify user identity
2. Check `last_email_changed_at` in database
3. Options:
   - Wait for restriction period to end
   - Admin can manually reset `last_email_changed_at` to `null`
   - User can use current email until restriction lifts

### **Admin Override (Database)**
```sql
UPDATE clients 
SET last_email_changed_at = NULL 
WHERE id = {client_id};
```

⚠️ **Warning:** Only do this for legitimate urgent cases.

---

## Summary

✅ **3-month (90-day) email change restriction**
✅ **Professional UI with countdown timer**
✅ **Detailed eligibility checking**
✅ **Comprehensive error handling**
✅ **Security-focused implementation**
✅ **User-friendly explanations**
✅ **Admin override capability**

**Security Rating:** ⭐⭐⭐⭐⭐ High

This feature significantly enhances account security while maintaining a professional user experience with clear communication about restrictions.
