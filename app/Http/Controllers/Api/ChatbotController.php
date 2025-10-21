<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Send a message to the chatbot
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string|max:255',
        ]);

        $client = $request->user();
        $message = $request->input('message');
        $sessionId = $request->input('session_id', Str::uuid()->toString());

        // Process the message and get AI response
        $result = $this->chatbotService->processMessage($client, $message, $sessionId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process your message',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $result['message'],
                'session_id' => $sessionId,
                'timestamp' => now()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Get chat history for a session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
        ]);

        $client = $request->user();
        $sessionId = $request->input('session_id');

        $history = $this->chatbotService->getSessionHistory($client, $sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'messages' => $history,
                'total' => count($history),
            ],
        ], 200);
    }

    /**
     * Create a new chat session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createSession(Request $request): JsonResponse
    {
        $client = $request->user();
        $sessionId = Str::uuid()->toString();

        // Create welcome message
        $welcomeMessage = "Hi {$client->name}! 👋 Welcome to EMOH Support. How can I help you today? I can assist you with property inquiries, rental information, account details, and general support questions.";

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'welcome_message' => $welcomeMessage,
                'client_name' => $client->name,
            ],
        ], 200);
    }

    /**
     * Clear chat history for a session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
        ]);

        $client = $request->user();
        $sessionId = $request->input('session_id');

        $deleted = $this->chatbotService->clearSessionHistory($client, $sessionId);

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Chat history cleared successfully' : 'No history found',
            'data' => [
                'session_id' => $sessionId,
                'deleted' => $deleted,
            ],
        ], 200);
    }

    /**
     * Get chatbot status and configuration
     * 
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        $isConfigured = !empty(config('services.openrouter.api_key')) 
                        && !empty(config('services.openrouter.model'));

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $isConfigured ? 'online' : 'offline',
                'model' => config('services.openrouter.model'),
                'configured' => $isConfigured,
                'features' => [
                    'knowledge_base' => true,
                    'conversation_history' => true,
                    'real_time' => true,
                ],
            ],
        ], 200);
    }
}
