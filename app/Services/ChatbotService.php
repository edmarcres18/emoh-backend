<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Property;
use App\Models\Rented;
use App\Models\ChatHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatbotService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->model = config('services.openrouter.model');
        $this->baseUrl = config('services.openrouter.base_url');
    }

    /**
     * Process user message and generate AI response with knowledge base context
     */
    public function processMessage(Client $client, string $message, string $sessionId): array
    {
        try {
            // Get knowledge base context based on user message
            $context = $this->getKnowledgeBaseContext($client, $message);

            // Get conversation history for context
            $history = $this->getConversationHistory($client, $sessionId);

            // Generate AI response
            $aiResponse = $this->generateAIResponse($message, $context, $history, $client);

            // Save user message to history
            ChatHistory::create([
                'client_id' => $client->id,
                'session_id' => $sessionId,
                'message' => $message,
                'sender' => 'user',
                'context' => null,
            ]);

            // Save bot response to history
            ChatHistory::create([
                'client_id' => $client->id,
                'session_id' => $sessionId,
                'message' => $aiResponse,
                'sender' => 'bot',
                'context' => $context,
            ]);

            return [
                'success' => true,
                'message' => $aiResponse,
                'context' => $context,
            ];
        } catch (Exception $e) {
            Log::error('Chatbot Error: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'message' => $message,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'I apologize, but I\'m having trouble processing your request right now. Please try again or contact our support team at support@emoh.com for immediate assistance.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get knowledge base context from database models
     */
    protected function getKnowledgeBaseContext(Client $client, string $message): array
    {
        $context = [
            'client_info' => null,
            'properties' => [],
            'rentals' => [],
        ];

        $messageLower = strtolower($message);

        // Check if query is about client's own information
        if ($this->isAboutClientInfo($messageLower)) {
            $context['client_info'] = [
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'email_verified' => $client->email_verified_at ? true : false,
                'active_status' => $client->is_active,
                'member_since' => $client->created_at->format('F Y'),
            ];
        }

        // Check if query is about properties
        if ($this->isAboutProperties($messageLower)) {
            $properties = Property::with(['category', 'location'])
                ->where('status', 'Available')
                ->orderBy('is_featured', 'desc')
                ->limit(5)
                ->get();

            $context['properties'] = $properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->property_name,
                    'category' => $property->category->category_name ?? 'N/A',
                    'location' => $property->location->location_name ?? 'N/A',
                    'monthly_rent' => '₱' . number_format($property->estimated_monthly, 2),
                    'lot_area' => $property->lot_area . ' sqm',
                    'floor_area' => $property->floor_area . ' sqm',
                    'status' => $property->status,
                ];
            })->toArray();
        }

        // Check if query is about rentals
        if ($this->isAboutRentals($messageLower)) {
            $rentals = Rented::with(['property', 'property.category', 'property.location'])
                ->where('client_id', $client->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $context['rentals'] = $rentals->map(function ($rental) {
                return [
                    'id' => $rental->id,
                    'property' => $rental->property->property_name,
                    'category' => $rental->property->category->category_name ?? 'N/A',
                    'location' => $rental->property->location->location_name ?? 'N/A',
                    'monthly_rent' => $rental->formatted_monthly_rent,
                    'start_date' => $rental->start_date->format('M d, Y'),
                    'end_date' => $rental->end_date ? $rental->end_date->format('M d, Y') : 'Ongoing',
                    'status' => $rental->status,
                    'remarks' => $rental->remarks,
                    'remaining_days' => $rental->remaining_days,
                ];
            })->toArray();
        }

        return $context;
    }

    /**
     * Check if message is about client information
     */
    protected function isAboutClientInfo(string $message): bool
    {
        $keywords = ['my account', 'my profile', 'my information', 'my email', 'my phone', 'my details', 'account info', 'profile info', 'who am i'];
        
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if message is about properties
     */
    protected function isAboutProperties(string $message): bool
    {
        $keywords = ['property', 'properties', 'available', 'rental', 'rent', 'house', 'apartment', 'lot', 'location', 'price', 'monthly', 'browse'];
        
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if message is about rentals
     */
    protected function isAboutRentals(string $message): bool
    {
        $keywords = ['my rental', 'my rentals', 'renting', 'rented', 'contract', 'lease', 'payment', 'due date', 'rent payment', 'rental history'];
        
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get conversation history for context
     */
    protected function getConversationHistory(Client $client, string $sessionId, int $limit = 6): array
    {
        return ChatHistory::where('client_id', $client->id)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function ($chat) {
                return [
                    'role' => $chat->sender === 'user' ? 'user' : 'assistant',
                    'content' => $chat->message,
                ];
            })
            ->toArray();
    }

    /**
     * Generate AI response using OpenRouter API
     */
    protected function generateAIResponse(string $userMessage, array $context, array $history, Client $client): string
    {
        // Build system prompt with knowledge base context
        $systemPrompt = $this->buildSystemPrompt($context, $client);

        // Prepare messages for API
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$history,
            ['role' => 'user', 'content' => $userMessage],
        ];

        // Call OpenRouter API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name') . ' Chatbot',
        ])
        ->timeout(30)
        ->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenRouter API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenRouter API');
        }

        return trim($data['choices'][0]['message']['content']);
    }

    /**
     * Build system prompt with knowledge base context
     */
    protected function buildSystemPrompt(array $context, Client $client): string
    {
        $prompt = "You are EMOH Support Assistant, a helpful and professional chatbot for EMOH Property Management System. ";
        $prompt .= "Your role is to assist clients with property inquiries, rental information, and general support questions. ";
        $prompt .= "Always be polite, concise, and provide accurate information based only on the data provided. ";
        $prompt .= "If you don't have specific information, politely inform the user and offer to connect them with support.\n\n";

        $prompt .= "Current User: " . $client->name . "\n";
        $prompt .= "User Status: " . ($client->email_verified_at ? 'Verified' : 'Unverified') . "\n\n";

        // Add client information context
        if (!empty($context['client_info'])) {
            $prompt .= "CLIENT INFORMATION:\n";
            $prompt .= "- Name: " . $context['client_info']['name'] . "\n";
            $prompt .= "- Email: " . $context['client_info']['email'] . "\n";
            $prompt .= "- Phone: " . ($context['client_info']['phone'] ?? 'Not provided') . "\n";
            $prompt .= "- Email Verified: " . ($context['client_info']['email_verified'] ? 'Yes' : 'No') . "\n";
            $prompt .= "- Member Since: " . $context['client_info']['member_since'] . "\n\n";
        }

        // Add properties context
        if (!empty($context['properties'])) {
            $prompt .= "AVAILABLE PROPERTIES:\n";
            foreach ($context['properties'] as $index => $property) {
                $prompt .= ($index + 1) . ". " . $property['name'] . "\n";
                $prompt .= "   - Category: " . $property['category'] . "\n";
                $prompt .= "   - Location: " . $property['location'] . "\n";
                $prompt .= "   - Monthly Rent: " . $property['monthly_rent'] . "\n";
                $prompt .= "   - Lot Area: " . $property['lot_area'] . "\n";
                $prompt .= "   - Floor Area: " . $property['floor_area'] . "\n\n";
            }
        }

        // Add rentals context
        if (!empty($context['rentals'])) {
            $prompt .= "USER'S RENTAL HISTORY:\n";
            foreach ($context['rentals'] as $index => $rental) {
                $prompt .= ($index + 1) . ". " . $rental['property'] . "\n";
                $prompt .= "   - Location: " . $rental['location'] . "\n";
                $prompt .= "   - Monthly Rent: " . $rental['monthly_rent'] . "\n";
                $prompt .= "   - Start Date: " . $rental['start_date'] . "\n";
                $prompt .= "   - End Date: " . $rental['end_date'] . "\n";
                $prompt .= "   - Status: " . $rental['status'] . "\n";
                $prompt .= "   - Remarks: " . $rental['remarks'] . "\n";
                if ($rental['remaining_days']) {
                    $prompt .= "   - Remaining Days: " . $rental['remaining_days'] . "\n";
                }
                $prompt .= "\n";
            }
        }

        $prompt .= "CONTACT INFORMATION:\n";
        $prompt .= "- Support Email: support@emoh.com\n";
        $prompt .= "- Phone: +1 (555) 123-4567\n";
        $prompt .= "- Business Hours: Monday to Friday, 9 AM - 6 PM\n\n";

        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "1. Only provide information based on the data given above\n";
        $prompt .= "2. Be concise and friendly in your responses\n";
        $prompt .= "3. If asked about something not in the data, politely say you don't have that information\n";
        $prompt .= "4. For urgent matters or complex issues, recommend contacting support directly\n";
        $prompt .= "5. Always format monetary values in Philippine Peso (₱) when discussing prices\n";
        $prompt .= "6. Provide actionable next steps when appropriate\n";

        return $prompt;
    }

    /**
     * Get chat history for a session
     */
    public function getSessionHistory(Client $client, string $sessionId): array
    {
        return ChatHistory::where('client_id', $client->id)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'text' => $chat->message,
                    'sender' => $chat->sender,
                    'timestamp' => $chat->created_at->toIso8601String(),
                    'context' => $chat->context,
                ];
            })
            ->toArray();
    }

    /**
     * Clear session history (optional feature)
     */
    public function clearSessionHistory(Client $client, string $sessionId): bool
    {
        return ChatHistory::where('client_id', $client->id)
            ->where('session_id', $sessionId)
            ->delete() > 0;
    }
}
