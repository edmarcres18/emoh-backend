<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'location_id',
        'images',
        'property_name',
        'estimated_monthly',
        'lot_area',
        'floor_area',
        'details',
        'status',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'images' => 'array',
        'estimated_monthly' => 'decimal:2',
        'lot_area' => 'decimal:2',
        'floor_area' => 'decimal:2',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the property.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the location that owns the property.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Locations::class, 'location_id');
    }

    /**
     * Get the rental records for the property.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rented::class);
    }

    /**
     * Get the active rental for the property.
     */
    public function activeRental(): HasMany
    {
        return $this->hasMany(Rented::class)->where('status', 'active');
    }

    /**
     * Check if the property is currently rented.
     */
    public function isRented(): bool
    {
        return $this->rentals()->where('status', 'active')->exists();
    }

    /**
     * Get the current tenant (client) if property is rented.
     */
    public function currentTenant()
    {
        $activeRental = $this->activeRental()->with('client')->first();
        return $activeRental ? $activeRental->client : null;
    }

    /**
     * Check if the property can be updated (no active rentals).
     */
    public function canBeUpdated(): bool
    {
        return !$this->isRented();
    }

    /**
     * Get the current monthly rent rate (from active rental or estimated).
     */
    public function getCurrentMonthlyRate(): ?float
    {
        $activeRental = $this->activeRental()->first();
        return $activeRental ? $activeRental->monthly_rent : $this->estimated_monthly;
    }

    /**
     * Validate that estimated_monthly can be changed.
     */
    public function canChangeEstimatedMonthly(): bool
    {
        // Cannot change estimated monthly if there are active rentals
        return !$this->isRented();
    }

    /**
     * Get rental history for this property.
     */
    public function getRentalHistory()
    {
        return $this->rentals()
            ->with(['client'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get total revenue generated from this property.
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->rentals()
            ->whereIn('status', ['active', 'expired', 'terminated'])
            ->sum('monthly_rent');
    }

    /**
     * Update property status based on rental activity.
     */
    public function updateStatusBasedOnRentals(): void
    {
        $hasActiveRental = $this->isRented();

        if ($hasActiveRental && $this->status !== 'Rented') {
            $this->update(['status' => 'Rented']);
        } elseif (!$hasActiveRental && $this->status === 'Rented') {
            $this->update(['status' => 'Available']);
        }
    }

    /**
     * Get the appropriate status based on rental activity.
     */
    public function getAppropriateStatus(): string
    {
        if ($this->isRented()) {
            return 'Rented';
        }

        // If not rented and currently marked as 'Rented', change to 'Available'
        if ($this->status === 'Rented') {
            return 'Available';
        }

        // Keep current status if it's not 'Rented' (could be 'Renovation', etc.)
        return $this->status;
    }

    /**
     * Force update property status based on current rental state.
     */
    public function syncStatusWithRentals(): bool
    {
        $appropriateStatus = $this->getAppropriateStatus();

        if ($this->status !== $appropriateStatus) {
            return $this->update(['status' => $appropriateStatus]);
        }

        return true;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion if property has active rentals
        static::deleting(function ($property) {
            if ($property->isRented()) {
                throw new \InvalidArgumentException(
                    'Cannot delete property with active rental contracts. Please terminate the rental first.'
                );
            }
        });

        // Validate estimated_monthly changes
        static::updating(function ($property) {
            if ($property->isDirty('estimated_monthly') && $property->isRented()) {
                throw new \InvalidArgumentException(
                    'Cannot change estimated monthly rate while property has active rental contracts.'
                );
            }
        });
    }
}
