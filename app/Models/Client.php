<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'google_id',
        'avatar',
        'email_verified_at',
        'last_email_changed_at',
        'is_active',
        'email_verification_otp',
        'otp_expires_at',
        'otp_attempts',
        'failed_login_attempts',
        'last_failed_login_at',
        'locked_until',
        'last_login_ip',
        'browser_fingerprint',
        'last_successful_login_at',
        'last_activity',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_email_changed_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_successful_login_at' => 'datetime',
            'last_activity' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Generate a new OTP for email verification
     */
    public function generateEmailVerificationOTP(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'email_verification_otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10), // OTP expires in 10 minutes
            'otp_attempts' => 0,
        ]);

        return $otp;
    }

    /**
     * Verify the provided OTP
     */
    public function verifyOTP(string $otp): bool
    {
        // Check if OTP has expired
        if (!$this->otp_expires_at || $this->otp_expires_at->isPast()) {
            return false;
        }

        // Check if too many attempts
        if ($this->otp_attempts >= 5) {
            return false;
        }

        // Increment attempts
        $this->increment('otp_attempts');

        // Check if OTP matches
        if ($this->email_verification_otp === $otp) {
            // Clear OTP data and mark email as verified
            $this->update([
                'email_verified_at' => now(),
                'email_verification_otp' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if OTP is expired
     */
    public function isOTPExpired(): bool
    {
        return !$this->otp_expires_at || $this->otp_expires_at->isPast();
    }

    /**
     * Check if OTP attempts exceeded
     */
    public function hasExceededOTPAttempts(): bool
    {
        return $this->otp_attempts >= 5;
    }

    /**
     * Get the rental records for the client.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rented::class);
    }

    /**
     * Get the active rentals for the client.
     */
    public function activeRentals(): HasMany
    {
        return $this->hasMany(Rented::class)->where('status', 'active');
    }

    /**
     * Get the rental history for the client.
     */
    public function rentalHistory(): HasMany
    {
        return $this->hasMany(Rented::class)->with(['property', 'property.category', 'property.location']);
    }

    /**
     * Check if the client has any active rentals.
     */
    public function hasActiveRentals(): bool
    {
        return $this->activeRentals()->exists();
    }

    /**
     * Get the total number of properties rented by the client.
     */
    public function getTotalRentalsAttribute(): int
    {
        return $this->rentals()->count();
    }

    /**
     * Check if client can change email (3 months restriction)
     * Returns true if client has never changed email or if 3 months have passed
     */
    public function canChangeEmail(): bool
    {
        // If never changed email, allow change
        if (!$this->last_email_changed_at) {
            return true;
        }

        // Check if 3 months have passed since last change
        return $this->last_email_changed_at->addMonths(3)->isPast();
    }

    /**
     * Get the date when client can change email again
     */
    public function getNextEmailChangeDate(): ?\Carbon\Carbon
    {
        if (!$this->last_email_changed_at) {
            return null;
        }

        return $this->last_email_changed_at->addMonths(3);
    }

    /**
     * Get days remaining until next email change is allowed
     */
    public function getDaysUntilEmailChange(): ?int
    {
        if ($this->canChangeEmail()) {
            return 0;
        }

        $nextChangeDate = $this->getNextEmailChangeDate();
        if (!$nextChangeDate) {
            return null;
        }

        return max(0, now()->diffInDays($nextChangeDate, false));
    }

    /**
     * Check if account is locked due to failed login attempts
     */
    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }

        if ($this->locked_until->isPast()) {
            // Lock expired, reset failed attempts
            $this->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedLogin(): void
    {
        $attempts = $this->failed_login_attempts + 1;
        $data = [
            'failed_login_attempts' => $attempts,
            'last_failed_login_at' => now(),
        ];

        // Lock account after 5 failed attempts for 15 minutes
        if ($attempts >= 5) {
            $data['locked_until'] = now()->addMinutes(15);
        }

        $this->update($data);
    }

    /**
     * Record successful login
     */
    public function recordSuccessfulLogin(string $ip, ?string $fingerprint = null): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'locked_until' => null,
            'last_login_ip' => $ip,
            'browser_fingerprint' => $fingerprint,
            'last_successful_login_at' => now(),
            'last_activity' => now(),
        ]);
    }

    /**
     * Get minutes remaining until account unlocks
     */
    public function getMinutesUntilUnlock(): ?int
    {
        if (!$this->locked_until) {
            return null;
        }

        if ($this->locked_until->isPast()) {
            return 0;
        }

        return max(0, now()->diffInMinutes($this->locked_until, false));
    }

    /**
     * Check if session has timed out (1 day of inactivity)
     */
    public function hasSessionTimedOut(): bool
    {
        if (!$this->last_activity) {
            return false;
        }

        // Session timeout after 24 hours (1 day) of inactivity
        return $this->last_activity->addDay()->isPast();
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    /**
     * Get hours since last activity
     */
    public function getHoursSinceLastActivity(): ?int
    {
        if (!$this->last_activity) {
            return null;
        }

        return (int) now()->diffInHours($this->last_activity);
    }
}
