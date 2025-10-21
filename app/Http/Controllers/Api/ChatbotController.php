<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatbotAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ChatbotController
 * 
 * Handles chatbot interactions for authenticated clients
 * Provides AI-powered responses based on client data analysis
 */
class ChatbotController extends Controller
{
    protected $aiService;

    public function __construct(ChatbotAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get or create conversation for authenticated client
     * 
     * @return JsonResponse
     */
    public function getOrCreateConversation(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');

            // Get or create active conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'status' => 'active'
                ],
                [
                    'title' => 'Support Chat',
                    'last_message_at' => now(),
                ]
            );

            // Load messages
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender' => $message->sender,
                        'content' => $message->content,
                        'timestamp' => $message->created_at->toIso8601String(),
                        'is_read' => $message->is_read,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'messages' => $messages,
                    'status' => $conversation->status,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send message and get AI response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'nullable|exists:conversations,id',
                'message' => 'required|string|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $client = $request->user('client');
            $userMessage = $request->input('message');

            // Get or create conversation
            if ($request->has('conversation_id')) {
                $conversation = Conversation::where('id', $request->conversation_id)
                    ->where('client_id', $client->id)
                    ->firstOrFail();
            } else {
                $conversation = Conversation::firstOrCreate(
                    [
                        'client_id' => $client->id,
                        'status' => 'active'
                    ],
                    [
                        'title' => 'Support Chat',
                        'last_message_at' => now(),
                    ]
                );
            }

            // Save user message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'user',
                'content' => $userMessage,
                'is_read' => true,
            ]);

            // Generate AI response
            $aiResponse = $this->aiService->generateResponse($userMessage, $client);
            $intent = $this->aiService->analyzeIntent($userMessage);

            // Save bot response
            $botMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'bot',
                'content' => $aiResponse['response'],
                'metadata' => [
                    'intent' => $intent,
                    'source' => $aiResponse['source'],
                    'ai_metadata' => $aiResponse['metadata'] ?? [],
                ],
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'user_message' => [
                        'id' => $message->id,
                        'content' => $message->content,
                        'timestamp' => $message->created_at->toIso8601String(),
                    ],
                    'bot_message' => [
                        'id' => $botMessage->id,
                        'content' => $botMessage->content,
                        'timestamp' => $botMessage->created_at->toIso8601String(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all conversations for authenticated client
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');

            $conversations = Conversation::where('client_id', $client->id)
                ->with('latestMessage')
                ->orderBy('last_message_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation
     * 
     * @param Request $request
     * @param int $conversationId
     * @return JsonResponse
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');

            $conversation = Conversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->firstOrFail();

            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender' => $message->sender,
                        'content' => $message->content,
                        'timestamp' => $message->created_at->toIso8601String(),
                        'is_read' => $message->is_read,
                        'metadata' => $message->metadata,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'messages' => $messages,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Mark conversation as resolved
     * 
     * @param Request $request
     * @param int $conversationId
     * @return JsonResponse
     */
    public function resolveConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');

            $conversation = Conversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->firstOrFail();

            $conversation->markAsResolved();

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as resolved',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete conversation and all messages
     * 
     * @param Request $request
     * @param int $conversationId
     * @return JsonResponse
     */
    public function deleteConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');

            $conversation = Conversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->firstOrFail();

            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
