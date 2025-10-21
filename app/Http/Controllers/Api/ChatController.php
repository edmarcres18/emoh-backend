<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Chat Controller
 * 
 * Handles real-time messaging between clients and support staff
 */
class ChatController extends Controller
{
    /**
     * Get or create conversation for authenticated client
     * If client has an open conversation, return it
     * Otherwise create a new one
     */
    public function getOrCreateConversation(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check for existing open conversation
            $conversation = Conversation::where('client_id', $client->id)
                ->where('status', 'open')
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'asc')
                        ->with(['conversation']);
                }, 'admin'])
                ->first();

            if (!$conversation) {
                // Create new conversation
                $conversation = Conversation::create([
                    'client_id' => $client->id,
                    'subject' => 'Support Request',
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);

                $conversation->load(['messages', 'admin']);
            }

            // Mark admin messages as read
            $conversation->markAsReadByClient();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $this->formatConversation($conversation, 'client'),
                ],
                'message' => 'Conversation retrieved successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Get/Create Conversation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all conversations for authenticated client
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $conversations = Conversation::where('client_id', $client->id)
                ->with(['latestMessage', 'admin'])
                ->orderBy('last_message_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversations' => $conversations->map(function ($conversation) {
                        return $this->formatConversationSummary($conversation, 'client');
                    }),
                    'pagination' => [
                        'current_page' => $conversations->currentPage(),
                        'last_page' => $conversations->lastPage(),
                        'per_page' => $conversations->perPage(),
                        'total' => $conversations->total(),
                    ],
                ],
                'message' => 'Conversations retrieved successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Get Conversations Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $conversation = Conversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }, 'admin'])
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            // Mark admin messages as read
            $conversation->markAsReadByClient();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $this->formatConversation($conversation, 'client'),
                ],
                'message' => 'Messages retrieved successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Get Messages Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Send a new message in a conversation
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'nullable|exists:conversations,id',
                'message' => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversationId = $request->input('conversation_id');

            // Get or create conversation
            if ($conversationId) {
                $conversation = Conversation::where('id', $conversationId)
                    ->where('client_id', $client->id)
                    ->first();

                if (!$conversation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found',
                    ], 404);
                }
            } else {
                // Create new conversation
                $conversation = Conversation::create([
                    'client_id' => $client->id,
                    'subject' => 'Support Request',
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);
            }

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $client->id,
                'sender_type' => 'client',
                'message' => $request->input('message'),
                'is_read' => false,
            ]);

            // Update conversation last message time
            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Reload message with conversation
            $message->load('conversation');

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $this->formatMessage($message, 'client'),
                    'conversation_id' => $conversation->id,
                ],
                'message' => 'Message sent successfully',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Send Message Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Poll for new messages
     * Returns new messages since the last message ID provided
     */
    public function pollMessages(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $lastMessageId = $request->query('last_message_id', 0);

            $conversation = Conversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            // Get new messages
            $newMessages = Message::where('conversation_id', $conversationId)
                ->where('id', '>', $lastMessageId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark admin messages as read
            $newMessages->where('sender_type', 'admin')
                ->each(function ($message) {
                    $message->markAsRead();
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $newMessages->map(function ($message) {
                        return $this->formatMessage($message, 'client');
                    }),
                    'has_new_messages' => $newMessages->isNotEmpty(),
                ],
                'message' => 'Messages polled successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Poll Messages Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to poll messages. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Format conversation for response
     */
    private function formatConversation(Conversation $conversation, string $userType): array
    {
        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $conversation->created_at->toIso8601String(),
            'admin' => $conversation->admin ? [
                'id' => $conversation->admin->id,
                'name' => $conversation->admin->name,
            ] : null,
            'unread_count' => $userType === 'client' 
                ? $conversation->unreadClientMessagesCount() 
                : $conversation->unreadAdminMessagesCount(),
            'messages' => $conversation->messages->map(function ($message) use ($userType) {
                return $this->formatMessage($message, $userType);
            }),
        ];
    }

    /**
     * Format conversation summary for response
     */
    private function formatConversationSummary(Conversation $conversation, string $userType): array
    {
        $latestMessage = $conversation->latestMessage->first();

        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $conversation->created_at->toIso8601String(),
            'admin' => $conversation->admin ? [
                'id' => $conversation->admin->id,
                'name' => $conversation->admin->name,
            ] : null,
            'unread_count' => $userType === 'client' 
                ? $conversation->unreadClientMessagesCount() 
                : $conversation->unreadAdminMessagesCount(),
            'latest_message' => $latestMessage ? [
                'message' => $latestMessage->message,
                'sender_type' => $latestMessage->sender_type,
                'created_at' => $latestMessage->created_at->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Format message for response
     */
    private function formatMessage(Message $message, string $userType): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_type' => $message->sender_type,
            'message' => $message->message,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
            'is_own' => ($userType === 'client' && $message->sender_type === 'client') ||
                       ($userType === 'admin' && $message->sender_type === 'admin'),
        ];
    }
}
