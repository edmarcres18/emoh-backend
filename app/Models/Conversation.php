<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'admin_id',
        'subject',
        'status',
        'last_message_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the client that owns the conversation.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the admin assigned to the conversation.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message for the conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Get unread messages count for client
     */
    public function unreadClientMessagesCount(): int
    {
        return $this->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get unread messages count for admin
     */
    public function unreadAdminMessagesCount(): int
    {
        return $this->messages()
            ->where('sender_type', 'client')
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark all messages as read for client
     */
    public function markAsReadByClient(): void
    {
        $this->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Mark all messages as read for admin
     */
    public function markAsReadByAdmin(): void
    {
        $this->messages()
            ->where('sender_type', 'client')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Check if conversation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Close the conversation
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Reopen the conversation
     */
    public function reopen(): void
    {
        $this->update(['status' => 'open']);
    }
}
