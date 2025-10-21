<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'sender',
        'content',
        'metadata',
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Check if message is from bot.
     */
    public function isFromBot(): bool
    {
        return $this->sender === 'bot';
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser(): bool
    {
        return $this->sender === 'user';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update conversation's last_message_at when a new message is created
        static::created(function ($message) {
            $message->conversation->updateLastMessageTime();
        });
    }
}
