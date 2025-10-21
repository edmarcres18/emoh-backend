<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'session_id',
        'role',
        'message',
        'context',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the client that owns the chat message.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to get messages for a specific session.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId)
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to get messages for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId)
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to get recent messages.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')
                    ->limit($limit);
    }

    /**
     * Get conversation history for context (last N messages).
     */
    public static function getConversationHistory(string $sessionId, ?int $clientId = null, int $limit = 20): array
    {
        $query = self::where('session_id', $sessionId);
        
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        
        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->reverse()
                    ->map(function ($message) {
                        return [
                            'role' => $message->role,
                            'content' => $message->message,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Create a new chat message.
     */
    public static function createMessage(
        string $sessionId,
        string $role,
        string $message,
        ?int $clientId = null,
        ?string $context = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'client_id' => $clientId,
            'session_id' => $sessionId,
            'role' => $role,
            'message' => $message,
            'context' => $context,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Clean up old messages (older than 90 days).
     */
    public static function cleanupOldMessages(): int
    {
        return self::where('created_at', '<', now()->subDays(90))->delete();
    }
}
