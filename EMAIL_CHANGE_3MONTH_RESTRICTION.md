# Email Change - 3-Month Restriction Feature

## Overview
A security feature that restricts clients from changing their email address more than once every 3 months (90 days).

---

## Security Rationale

### Why 3-Month Restriction?
1. **Prevents Account Takeover**: Limits attackers from rapidly changing email addresses
2. **Reduces Social Engineering**: Makes it harder for bad actors to manipulate account recovery
3. **Audit Trail**: Ensures email changes are intentional and well-documented
4. **Account Stability**: Discourages frequent changes that could indicate compromised accounts

---

## Database Changes

### Migration Created
**File:** `database/migrations/2025_10_18_004000_add_last_email_changed_at_to_clients_table.php`

**Column Added:** `last_email_changed_at` (nullable timestamp)
- Tracks the last time the client changed their email
- NULL for clients who have never changed their email
- Automatically cast to Carbon datetime object

### Run Migration
```bash
php artisan migrate
```

---

## Backend Implementation

### 1. **Client Model Updates** (`app/Models/Client.php`)

**New Methods:**

```php
/**
 * Check if client can change email (3 months restriction)
 */
public function canChangeEmail(): bool
{
    // If never changed email before, allow change
    if (!$this->last_email_changed_at) {
        return true;
    }

    // Check if 3 months (90 days) have passed since last change
    return $this->last_email_changed_at->addMonths(3)->isPast();
}

/**
 * Get the number of days remaining before email can be changed again
 */
public function daysUntilEmailChangeAllowed(): int
{
    if (!$this->last_email_changed_at) {
        return 0;
    }

    $nextAllowedDate = $this->last_email_changed_at->addMonths(3);
    
    if ($nextAllowedDate->isPast()) {
        return 0;
    }

    return now()->diffInDays($nextAllowedDate, false);
}
```

### 2. **Controller Updates** (`app/Http/Controllers/Api/ClientAuthController.php`)

**`requestEmailChange()` Method:**
- Added validation check before generating OTP
- Returns 403 error if restriction applies
- Provides days remaining and next allowed date

**Error Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "For security reasons, you can only change your email once every 3 months. You can change your email again on December 18, 2025.",
  "can_change_email": false,
  "days_remaining": 45,
  "next_allowed_date": "December 18, 2025"
}
```

**`verifyEmailChange()` Method:**
- Sets `last_email_changed_at` to current timestamp on successful verification
- This timestamp is used for future restriction checks

---

## Frontend Implementation

### 1. **ChangeEmailModal Component Updates**

**New State:**
```typescript
const [emailChangeRestriction, setEmailChangeRestriction] = useState<{
  canChange: boolean;
  daysRemaining: number;
  nextAllowedDate: string;
} | null>(null);
```

**Error Handling:**
- Detects 403 status with `can_change_email: false`
- Stores restriction details in state
- Displays enhanced error message with countdown

**UI Enhancements:**
- Shows days remaining until next allowed change
- Displays formatted next allowed date
- Disables email input when restricted
- Disables submit button when restricted
- Resets restriction state when modal closes

**Error Display:**
```
┌─────────────────────────────────────────────┐
│ ⚠️ For security reasons, you can only      │
│    change your email once every 3 months.  │
│    You can change your email again on      │
│    December 18, 2025.                       │
├─────────────────────────────────────────────┤
│ Days remaining: 45 days                     │
│ Next allowed date: December 18, 2025        │
└─────────────────────────────────────────────┘
```

---

## User Experience Flow

### Scenario 1: First Email Change (Allowed)
1. Client requests email change
2. No `last_email_changed_at` value exists
3. ✅ **Request approved** - OTP sent immediately
4. Client verifies new email
5. `last_email_changed_at` = current date

### Scenario 2: Second Email Change (Within 3 Months - Blocked)
1. Client requests email change (45 days after last change)
2. System checks: `last_email_changed_at + 3 months > now()`
3. ❌ **Request denied** - 403 error returned
4. Error message displays:
   - Days remaining: 45 days
   - Next allowed date: December 18, 2025
5. Email input and submit button disabled

### Scenario 3: Email Change (After 3 Months - Allowed)
1. Client requests email change (95 days after last change)
2. System checks: `last_email_changed_at + 3 months < now()`
3. ✅ **Request approved** - OTP sent immediately
4. Client verifies new email
5. `last_email_changed_at` updated to new timestamp

---

## API Endpoints

### POST `/api/client/request-email-change`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "new_email": "newemail@example.com"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "A verification code has been sent to your new email address.",
  "data": {
    "new_email": "newemail@example.com",
    "expires_at": "2025-10-18T09:15:00.000000Z"
  }
}
```

**Restriction Error (403 Forbidden):**
```json
{
  "success": false,
  "message": "For security reasons, you can only change your email once every 3 months. You can change your email again on December 18, 2025.",
  "can_change_email": false,
  "days_remaining": 45,
  "next_allowed_date": "December 18, 2025"
}
```

---

## Testing

### Test Case 1: First Email Change
```php
// Given: Client has never changed email
$client = Client::factory()->create([
    'email' => 'old@example.com',
    'last_email_changed_at' => null
]);

// When: Client requests email change
$response = $this->actingAs($client, 'client')
    ->postJson('/api/client/request-email-change', [
        'new_email' => 'new@example.com'
    ]);

// Then: Request should succeed
$response->assertOk();
$response->assertJson(['success' => true]);
```

### Test Case 2: Email Change Within 3 Months (Blocked)
```php
// Given: Client changed email 30 days ago
$client = Client::factory()->create([
    'email' => 'current@example.com',
    'last_email_changed_at' => now()->subDays(30)
]);

// When: Client requests email change
$response = $this->actingAs($client, 'client')
    ->postJson('/api/client/request-email-change', [
        'new_email' => 'newer@example.com'
    ]);

// Then: Request should be denied
$response->assertStatus(403);
$response->assertJson([
    'success' => false,
    'can_change_email' => false
]);
$this->assertArrayHasKey('days_remaining', $response->json());
```

### Test Case 3: Email Change After 3 Months (Allowed)
```php
// Given: Client changed email 95 days ago
$client = Client::factory()->create([
    'email' => 'current@example.com',
    'last_email_changed_at' => now()->subDays(95)
]);

// When: Client requests email change
$response = $this->actingAs($client, 'client')
    ->postJson('/api/client/request-email-change', [
        'new_email' => 'newest@example.com'
    ]);

// Then: Request should succeed
$response->assertOk();
$response->assertJson(['success' => true]);
```

---

## Admin Considerations

### Manual Override (If Needed)
If an admin needs to allow a client to change their email before the 3-month period:

```php
// Reset last_email_changed_at to allow immediate change
$client->update(['last_email_changed_at' => null]);
```

Or set it to a past date:
```php
// Allow change by setting to 3+ months ago
$client->update(['last_email_changed_at' => now()->subMonths(4)]);
```

### Check Client's Email Change Status
```php
$client = Client::find($clientId);

if ($client->canChangeEmail()) {
    echo "Client can change email now";
} else {
    $days = $client->daysUntilEmailChangeAllowed();
    echo "Client must wait {$days} more days";
}
```

---

## Security Benefits

| Threat | How 3-Month Restriction Helps |
|--------|-------------------------------|
| **Account Takeover** | Attacker can't rapidly change email after gaining access |
| **Phishing Recovery** | Limits damage if credentials are compromised |
| **Social Engineering** | Prevents quick email changes during support scams |
| **Data Exfiltration** | Slows down attackers trying to lock out legitimate users |
| **Audit Trail** | Creates clear timeline of account modifications |

---

## Configuration

### Change Restriction Period (If Needed)

To modify the 3-month restriction to a different period, update the Client model:

```php
// Change from 3 months to 6 months
public function canChangeEmail(): bool
{
    if (!$this->last_email_changed_at) {
        return true;
    }
    
    return $this->last_email_changed_at->addMonths(6)->isPast();
}
```

Or use days instead:
```php
// 90 days = 3 months
return $this->last_email_changed_at->addDays(90)->isPast();
```

---

## Future Enhancements

- [ ] Add admin panel to view client email change history
- [ ] Send notification to old email when email change is initiated
- [ ] Log all email change attempts (successful and blocked)
- [ ] Add configurable restriction period via settings table
- [ ] Implement progressive delays (first change: 1 month, second: 3 months, etc.)
- [ ] Add bypass for verified support tickets

---

## Summary

✅ **Database migration** created and ready
✅ **Backend validation** enforces 3-month restriction  
✅ **Frontend UI** handles restriction gracefully  
✅ **Error messages** provide clear feedback  
✅ **Security** improved with rate limiting  
✅ **User experience** maintained with helpful messaging

**Clients can now change their email only once every 3 months for enhanced security! 🔒**
