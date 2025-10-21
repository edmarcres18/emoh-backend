<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientConversation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'client_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'title',
        'status',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the client that owns the conversation.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ClientMessage::class, 'conversation_id');
    }

    /**
     * Get the latest messages for the conversation.
     */
    public function latestMessages(int $limit = 10): HasMany
    {
        return $this->messages()->latest()->limit($limit);
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Mark conversation as archived.
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Mark conversation as closed.
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Get the message count.
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }
}
