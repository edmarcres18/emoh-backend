<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    /**
     * Send a chat message and get AI response.
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $request->input('message');
            $sessionId = $request->input('session_id', Str::uuid()->toString());
            $clientId = auth('client')->id();

            // Save user message
            ChatMessage::createMessage(
                $sessionId,
                'user',
                $message,
                $clientId
            );

            // Get conversation history
            $history = ChatMessage::getConversationHistory($sessionId, $clientId, 10);

            // Get AI response
            $response = $this->openRouterService->chat($history, $clientId);

            if ($response['success']) {
                // Save assistant response
                ChatMessage::createMessage(
                    $sessionId,
                    'assistant',
                    $response['message'],
                    $clientId,
                    null,
                    [
                        'model' => $response['model'] ?? null,
                        'usage' => $response['usage'] ?? null,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => $response['message'],
                    'session_id' => $sessionId,
                    'model' => $response['model'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response['error'],
                'session_id' => $sessionId,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your request',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Stream chat response (Server-Sent Events).
     */
    public function chatStream(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = $request->input('message');
        $sessionId = $request->input('session_id', Str::uuid()->toString());
        $clientId = auth('client')->id();

        // Save user message
        ChatMessage::createMessage(
            $sessionId,
            'user',
            $message,
            $clientId
        );

        // Get conversation history
        $history = ChatMessage::getConversationHistory($sessionId, $clientId, 10);

        return response()->stream(function () use ($history, $clientId, $sessionId) {
            $fullResponse = '';
            
            foreach ($this->openRouterService->chatStream($history, $clientId) as $chunk) {
                $fullResponse .= $chunk;
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Save complete assistant response
            if (!empty($fullResponse)) {
                ChatMessage::createMessage(
                    $sessionId,
                    'assistant',
                    $fullResponse,
                    $clientId
                );
            }

            echo "data: [DONE]\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get chat history for a session.
     */
    public function getHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $limit = $request->input('limit', 50);
            $clientId = auth('client')->id();

            $query = ChatMessage::where('session_id', $sessionId);
            
            if ($clientId) {
                $query->where('client_id', $clientId);
            }

            $messages = $query->orderBy('created_at', 'asc')
                             ->limit($limit)
                             ->get()
                             ->map(function ($msg) {
                                 return [
                                     'id' => $msg->id,
                                     'role' => $msg->role,
                                     'message' => $msg->message,
                                     'created_at' => $msg->created_at->toIso8601String(),
                                 ];
                             });

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching chat history',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Clear chat history for a session.
     */
    public function clearHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $request->input('session_id');
            $clientId = auth('client')->id();

            $query = ChatMessage::where('session_id', $sessionId);
            
            if ($clientId) {
                $query->where('client_id', $clientId);
            }

            $deleted = $query->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat history cleared successfully',
                'deleted_count' => $deleted,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while clearing chat history',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get user's chat sessions.
     */
    public function getSessions(Request $request)
    {
        try {
            $clientId = auth('client')->id();

            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            $sessions = ChatMessage::where('client_id', $clientId)
                ->select('session_id')
                ->selectRaw('COUNT(*) as message_count')
                ->selectRaw('MAX(created_at) as last_message_at')
                ->selectRaw('MIN(created_at) as first_message_at')
                ->groupBy('session_id')
                ->orderBy('last_message_at', 'desc')
                ->get()
                ->map(function ($session) {
                    // Get first message as preview
                    $firstMessage = ChatMessage::where('session_id', $session->session_id)
                        ->where('role', 'user')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    return [
                        'session_id' => $session->session_id,
                        'message_count' => $session->message_count,
                        'last_message_at' => $session->last_message_at,
                        'first_message_at' => $session->first_message_at,
                        'preview' => $firstMessage ? Str::limit($firstMessage->message, 100) : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'sessions' => $sessions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching sessions',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Validate chatbot configuration.
     */
    public function validateConfig(Request $request)
    {
        try {
            $validation = $this->openRouterService->validateConfig();

            return response()->json([
                'success' => true,
                'validation' => $validation,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while validating configuration',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
