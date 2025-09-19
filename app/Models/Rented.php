<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Rented extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rented';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'property_id',
        'monthly_rent',
        'security_deposit',
        'start_date',
        'end_date',
        'status',
        'terms_conditions',
        'notes',
        'documents',
        'contract_signed_at',
        'remarks',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'documents' => 'array',
        'contract_signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the client that owns the rental.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the property that is rented.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope a query to only include active rentals.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expired rentals.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired')
                    ->orWhere(function ($query) {
                        $query->where('end_date', '<', now()->toDateString())
                              ->where('status', 'active');
                    });
    }

    /**
     * Scope a query to only include pending rentals.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include terminated rentals.
     */
    public function scopeTerminated(Builder $query): Builder
    {
        return $query->where('status', 'terminated');
    }

    /**
     * Check if the rental is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->start_date <= now()->toDateString() &&
               ($this->end_date === null || $this->end_date >= now()->toDateString());
    }

    /**
     * Check if the rental has expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date < now()->toDateString();
    }

    /**
     * Get the remaining days of the rental.
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->end_date || $this->status !== 'active') {
            return null;
        }

        $remaining = Carbon::parse($this->end_date)->diffInDays(now(), false);
        return $remaining < 0 ? abs($remaining) : 0;
    }

    /**
     * Get the total rental duration in days.
     */
    public function getTotalDurationAttribute(): int
    {
        $endDate = $this->end_date ?? now()->toDateString();
        return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($endDate)) + 1;
    }

    /**
     * Get the total amount paid (monthly rent * months).
     */
    public function getTotalAmountAttribute(): float
    {
        $months = Carbon::parse($this->start_date)->diffInMonths($this->end_date ?? now());
        return $this->monthly_rent * max(1, $months);
    }

    /**
     * Terminate the rental contract.
     */
    public function terminate(string $reason = null): bool
    {
        $this->status = 'terminated';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Terminated: " . $reason;
        }
        return $this->save();
    }

    /**
     * Activate the rental contract.
     */
    public function activate(): bool
    {
        $this->status = 'active';
        if (!$this->contract_signed_at) {
            $this->contract_signed_at = now();
        }
        return $this->save();
    }

    /**
     * End the rental contract (not renewed).
     */
    public function end(string $reason = null): bool
    {
        $this->status = 'ended';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Ended (Not Renewed): " . $reason;
        }
        return $this->save();
    }

    /**
     * Mark rental as expired.
     */
    public function markAsExpired(): bool
    {
        $this->status = 'expired';
        return $this->save();
    }

    /**
     * Get formatted monthly rent.
     */
    public function getFormattedMonthlyRentAttribute(): string
    {
        return '₱' . number_format($this->monthly_rent, 2);
    }

    /**
     * Get formatted security deposit.
     */
    public function getFormattedSecurityDepositAttribute(): string
    {
        return $this->security_deposit ? '₱' . number_format($this->security_deposit, 2) : 'N/A';
    }

    /**
     * Set monthly rent from property's estimated monthly rate.
     */
    public function setMonthlyRentFromProperty(): void
    {
        if ($this->property_id && $this->property) {
            $this->monthly_rent = $this->property->estimated_monthly;
        }
    }

    /**
     * Validate that monthly rent matches property's estimated monthly rate.
     */
    public function validateMonthlyRentMatchesProperty(): bool
    {
        if (!$this->property_id || !$this->property) {
            return false;
        }

        return bccomp($this->monthly_rent, $this->property->estimated_monthly, 2) === 0;
    }

    /**
     * Get the property's estimated monthly rate.
     */
    public function getPropertyEstimatedMonthlyAttribute(): ?float
    {
        return $this->property ? $this->property->estimated_monthly : null;
    }

    /**
     * Get automatic remarks based on end_date comparison.
     */
    public function getRemarksAttribute(): string
    {
        if (!$this->end_date) {
            return 'No end date set';
        }

        $now = now();
        $endDate = Carbon::parse($this->end_date);
        $daysDifference = $now->diffInDays($endDate, false);

        if ($daysDifference > 5) {
            return 'Active';
        } elseif ($daysDifference == 5) {
            return 'Almost Due Date';
        } elseif ($daysDifference == 0) {
            return 'Due Date Today';
        } elseif ($daysDifference < 0) {
            $overdueDays = abs($daysDifference);
            return "Over Due ({$overdueDays} day" . ($overdueDays > 1 ? 's' : '') . ")";
        } else {
            return 'Due Soon';
        }
    }

    /**
     * Update remarks automatically based on end_date.
     */
    public function updateRemarks(): void
    {
        $this->remarks = $this->getRemarksAttribute();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set monthly_rent from property's estimated_monthly when creating
        static::creating(function ($rental) {
            if ($rental->property_id && !$rental->monthly_rent) {
                $property = Property::find($rental->property_id);
                if ($property && $property->estimated_monthly) {
                    $rental->monthly_rent = $property->estimated_monthly;
                }
            }
        });

        // Validate monthly_rent matches property's estimated_monthly when saving
        static::saving(function ($rental) {
            // Ensure monthly_rent matches property's estimated_monthly
            if ($rental->property_id) {
                $property = $rental->property ?? Property::find($rental->property_id);
                if ($property && $property->estimated_monthly) {
                    // Convert to float for comparison to handle string/numeric type issues
                    $monthlyRent = (float) $rental->monthly_rent;
                    $estimatedMonthly = (float) $property->estimated_monthly;

                    // Allow small floating point differences (0.01)
                    if (abs($monthlyRent - $estimatedMonthly) > 0.01) {
                        throw new \InvalidArgumentException(
                            "Monthly rent (₱" . number_format($monthlyRent, 2) .
                            ") must match the property's estimated monthly rate (₱" .
                            number_format($estimatedMonthly, 2) . ")."
                        );
                    }
                }
            }

            // Automatically update remarks based on end_date
            $rental->updateRemarks();
        });

        // Validate property availability when creating/updating
        static::saving(function ($rental) {
            if ($rental->status === 'active' && $rental->property_id) {
                $existingActiveRental = static::where('property_id', $rental->property_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $rental->id ?? 0)
                    ->exists();

                if ($existingActiveRental) {
                    throw new \InvalidArgumentException(
                        'This property already has an active rental contract.'
                    );
                }
            }
        });

        // Update property status after rental is saved
        static::saved(function ($rental) {
            if ($rental->property_id && $rental->property) {
                $rental->property->updateStatusBasedOnRentals();
            }
        });

        // Update property status after rental is deleted
        static::deleted(function ($rental) {
            if ($rental->property_id) {
                $property = Property::find($rental->property_id);
                if ($property) {
                    $property->updateStatusBasedOnRentals();
                }
            }
        });

        // Update property status when rental status changes
        static::updated(function ($rental) {
            if ($rental->wasChanged('status') && $rental->property_id) {
                if ($rental->property) {
                    $rental->property->updateStatusBasedOnRentals();
                }
            }
        });
    }
}
