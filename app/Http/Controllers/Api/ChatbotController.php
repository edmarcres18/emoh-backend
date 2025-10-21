<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientConversation;
use App\Models\ClientMessage;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Chatbot Controller
 * 
 * Handles all chatbot-related API endpoints with secure authentication.
 * Protected by auth:client middleware.
 */
class ChatbotController extends Controller
{
    protected OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    /**
     * Send a message to the chatbot
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'conversation_id' => 'nullable|exists:client_conversations,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $client = $request->user('client');
            $message = $request->input('message');
            $conversationId = $request->input('conversation_id');

            // Get or create conversation
            $conversation = $this->getOrCreateConversation($client->id, $conversationId);

            // Verify conversation belongs to client
            if ($conversation->client_id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to conversation',
                ], 403);
            }

            // Save client message
            $clientMessage = ClientMessage::create([
                'conversation_id' => $conversation->id,
                'sender' => 'client',
                'message' => $message,
            ]);

            // Get conversation history
            $conversationHistory = $this->getConversationHistory($conversation->id);

            // Build context
            $context = $this->buildClientContext($client);

            // Get AI response
            $aiResponse = $this->openRouterService->generateContextualResponse(
                $message,
                $context,
                $conversationHistory
            );

            // Handle AI response
            if (!$aiResponse['success']) {
                // Use fallback response
                $responseMessage = $this->openRouterService->getFallbackResponse($message);
                $metadata = ['fallback' => true, 'error' => $aiResponse['error'] ?? 'AI service unavailable'];
            } else {
                $responseMessage = $aiResponse['message'];
                $metadata = [
                    'model' => $aiResponse['model'] ?? null,
                    'usage' => $aiResponse['usage'] ?? null,
                    'finish_reason' => $aiResponse['finish_reason'] ?? null,
                ];
            }

            // Save AI message
            $aiMessage = ClientMessage::create([
                'conversation_id' => $conversation->id,
                'sender' => 'ai',
                'message' => $responseMessage,
                'metadata' => $metadata,
            ]);

            // Update conversation timestamp
            $conversation->updateLastMessageTime();

            // Update conversation title if it's the first message
            if ($conversation->messages()->count() <= 2 && !$conversation->title) {
                $conversation->update([
                    'title' => $this->generateConversationTitle($message)
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'client_message' => [
                        'id' => $clientMessage->id,
                        'message' => $clientMessage->message,
                        'sender' => $clientMessage->sender,
                        'created_at' => $clientMessage->created_at,
                    ],
                    'ai_message' => [
                        'id' => $aiMessage->id,
                        'message' => $aiMessage->message,
                        'sender' => $aiMessage->sender,
                        'created_at' => $aiMessage->created_at,
                        'is_fallback' => $metadata['fallback'] ?? false,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot sendMessage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message. Please try again.',
            ], 500);
        }
    }

    /**
     * Get conversation history
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $client = $request->user('client');
            
            $conversations = ClientConversation::where('client_id', $client->id)
                ->where('status', '!=', 'closed')
                ->with(['messages' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('last_message_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot getConversations error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversations',
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
            
            $conversation = ClientConversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $messages = ClientMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $conversation,
                    'messages' => $messages,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot getMessages error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages',
            ], 500);
        }
    }

    /**
     * Delete a conversation
     * 
     * @param Request $request
     * @param int $conversationId
     * @return JsonResponse
     */
    public function deleteConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');
            
            $conversation = ClientConversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            // Soft delete conversation and messages
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot deleteConversation error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
            ], 500);
        }
    }

    /**
     * Archive a conversation
     * 
     * @param Request $request
     * @param int $conversationId
     * @return JsonResponse
     */
    public function archiveConversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $client = $request->user('client');
            
            $conversation = ClientConversation::where('id', $conversationId)
                ->where('client_id', $client->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $conversation->archive();

            return response()->json([
                'success' => true,
                'message' => 'Conversation archived successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot archiveConversation error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive conversation',
            ], 500);
        }
    }

    /**
     * Get or create conversation
     * 
     * @param int $clientId
     * @param int|null $conversationId
     * @return ClientConversation
     */
    protected function getOrCreateConversation(int $clientId, ?int $conversationId = null): ClientConversation
    {
        if ($conversationId) {
            $conversation = ClientConversation::find($conversationId);
            if ($conversation && $conversation->client_id === $clientId) {
                return $conversation;
            }
        }

        // Create new conversation
        return ClientConversation::create([
            'client_id' => $clientId,
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    /**
     * Get conversation history
     * 
     * @param int $conversationId
     * @return array
     */
    protected function getConversationHistory(int $conversationId): array
    {
        $messages = ClientMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit(config('services.openrouter.max_context_messages', 10))
            ->get()
            ->reverse()
            ->values()
            ->toArray();

        return $messages;
    }

    /**
     * Build client context for AI
     * 
     * @param $client
     * @return array
     */
    protected function buildClientContext($client): array
    {
        $context = [
            'client' => [
                'name' => $client->name,
                'email' => $client->email,
                'email_verified_at' => $client->email_verified_at,
                'is_active' => $client->is_active,
            ],
        ];

        // Load rental information
        try {
            $rentals = $client->rentals()
                ->with(['property', 'property.category', 'property.location'])
                ->get()
                ->toArray();
            
            if (!empty($rentals)) {
                $context['rentals'] = $rentals;
            }

            // Get available properties count
            $availablePropertiesCount = DB::table('properties')
                ->where('status', 'available')
                ->count();
            
            $context['available_properties'] = $availablePropertiesCount;

        } catch (\Exception $e) {
            Log::warning('Failed to load rental context', ['error' => $e->getMessage()]);
        }

        return $context;
    }

    /**
     * Generate conversation title from first message
     * 
     * @param string $message
     * @return string
     */
    protected function generateConversationTitle(string $message): string
    {
        // Limit to first 50 characters
        $title = substr($message, 0, 50);
        
        // Add ellipsis if truncated
        if (strlen($message) > 50) {
            $title .= '...';
        }

        return $title;
    }
}
