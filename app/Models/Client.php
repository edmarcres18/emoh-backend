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
        'is_active',
        'email_verification_otp',
        'otp_expires_at',
        'otp_attempts',
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
            'otp_expires_at' => 'datetime',
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
}
