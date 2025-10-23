<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestInquiry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
        'ip_address',
        'user_agent',
        'status',
        'read_at',
        'replied_at',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include pending inquiries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include read inquiries.
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope a query to only include replied inquiries.
     */
    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the subject label.
     */
    public function getSubjectLabelAttribute(): string
    {
        return match($this->subject) {
            'rental' => 'Property Rental Inquiry',
            'lease' => 'Property Lease Inquiry',
            'general' => 'General Information',
            'support' => 'Customer Support',
            default => ucfirst($this->subject),
        };
    }

    /**
     * Mark inquiry as read.
     */
    public function markAsRead(): void
    {
        if ($this->status === 'pending') {
            $this->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark inquiry as replied.
     */
    public function markAsReplied(): void
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now(),
        ]);
    }
}
