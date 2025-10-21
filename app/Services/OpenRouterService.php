<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * OpenRouter AI Service
 * 
 * Scalable service for integrating with OpenRouter AI API.
 * Supports multiple models, caching, and error handling.
 */
class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->model = config('services.openrouter.model', 'deepseek/deepseek-chat-v3.1:free');
        $this->maxTokens = config('services.openrouter.max_tokens', 2000);
        $this->temperature = config('services.openrouter.temperature', 0.7);
    }

    /**
     * Send a chat completion request to OpenRouter
     * 
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param array $options Additional options to override defaults
     * @return array Response from OpenRouter API
     * @throws \Exception
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        try {
            // Validate API key
            if (empty($this->apiKey) || $this->apiKey === 'your_openrouter_api_key_here') {
                throw new \Exception('OpenRouter API key is not configured. Please set OPENROUTER_API_KEY in your .env file.');
            }

            $payload = [
                'model' => $options['model'] ?? $this->model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                'temperature' => $options['temperature'] ?? $this->temperature,
            ];

            // Add optional parameters if provided
            if (isset($options['top_p'])) {
                $payload['top_p'] = $options['top_p'];
            }
            if (isset($options['frequency_penalty'])) {
                $payload['frequency_penalty'] = $options['frequency_penalty'];
            }
            if (isset($options['presence_penalty'])) {
                $payload['presence_penalty'] = $options['presence_penalty'];
            }

            // Make API request with timeout and retry logic
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name', 'EMOH'),
            ])
                ->timeout(60)
                ->retry(3, 1000)
                ->post($this->baseUrl . '/chat/completions', $payload);

            // Check if request was successful
            if (!$response->successful()) {
                $error = $response->json('error.message', 'Unknown error');
                Log::error('OpenRouter API Error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'response' => $response->body(),
                ]);
                throw new \Exception('AI service error: ' . $error);
            }

            $data = $response->json();

            // Validate response structure
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response structure from AI service');
            }

            return [
                'success' => true,
                'message' => $data['choices'][0]['message']['content'],
                'model' => $data['model'] ?? $this->model,
                'usage' => $data['usage'] ?? null,
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('OpenRouter Service Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback' => true,
            ];
        }
    }

    /**
     * Generate a contextual response based on client data
     * 
     * @param string $userMessage User's message
     * @param array $context Additional context (client data, rental info, etc.)
     * @param array $conversationHistory Previous messages for context
     * @return array AI response
     */
    public function generateContextualResponse(
        string $userMessage,
        array $context = [],
        array $conversationHistory = []
    ): array {
        // Build system prompt with context
        $systemPrompt = $this->buildSystemPrompt($context);

        // Prepare messages array
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add conversation history (limit to recent messages for efficiency)
        $maxContextMessages = config('services.openrouter.max_context_messages', 10);
        $recentHistory = array_slice($conversationHistory, -$maxContextMessages);
        
        foreach ($recentHistory as $msg) {
            $messages[] = [
                'role' => $msg['sender'] === 'client' ? 'user' : 'assistant',
                'content' => $msg['message']
            ];
        }

        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // Get AI response
        return $this->chatCompletion($messages);
    }

    /**
     * Build system prompt with client context
     * 
     * @param array $context Client data and context
     * @return string System prompt
     */
    protected function buildSystemPrompt(array $context): string
    {
        $prompt = "You are a helpful AI assistant for EMOH, a professional property rental platform. ";
        $prompt .= "You help clients with property inquiries, rental information, account management, and general support. ";
        $prompt .= "Always be professional, friendly, and concise. ";
        $prompt .= "If you don't know something, recommend contacting support or checking the relevant section.\n\n";

        // Add client context
        if (isset($context['client'])) {
            $client = $context['client'];
            $prompt .= "Client Information:\n";
            $prompt .= "- Name: {$client['name']}\n";
            $prompt .= "- Email: {$client['email']}\n";
            
            if (isset($client['email_verified_at'])) {
                $prompt .= "- Email Status: " . ($client['email_verified_at'] ? "Verified" : "Not Verified") . "\n";
            }
            
            if (isset($client['is_active'])) {
                $prompt .= "- Account Status: " . ($client['is_active'] ? "Active" : "Inactive") . "\n";
            }
        }

        // Add rental context
        if (isset($context['rentals']) && !empty($context['rentals'])) {
            $rentalCount = count($context['rentals']);
            $prompt .= "\nRental Information:\n";
            $prompt .= "- Total Rentals: {$rentalCount}\n";
            
            foreach ($context['rentals'] as $index => $rental) {
                $prompt .= "- Property " . ($index + 1) . ": ";
                $prompt .= ($rental['property']['title'] ?? 'Property') . " ";
                $prompt .= "(" . ($rental['status'] ?? 'unknown status') . ")\n";
            }
        }

        // Add property context if searching
        if (isset($context['available_properties'])) {
            $prompt .= "\nThere are {$context['available_properties']} properties available for rent.\n";
        }

        $prompt .= "\nImportant:\n";
        $prompt .= "- Contact support: support@emoh.com or +1 (555) 123-4567\n";
        $prompt .= "- Business hours: Monday to Friday, 9 AM - 6 PM\n";
        $prompt .= "- For urgent issues, recommend contacting support directly\n";

        return $prompt;
    }

    /**
     * Check if OpenRouter service is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'your_openrouter_api_key_here';
    }

    /**
     * Get fallback response when AI service is unavailable
     * 
     * @param string $userMessage User's message
     * @return string Fallback response
     */
    public function getFallbackResponse(string $userMessage): string
    {
        $input = strtolower($userMessage);

        // Pattern matching for common queries
        if (str_contains($input, 'property') || str_contains($input, 'rental')) {
            return 'I can help you find properties! You can browse our available properties by clicking "Browse Properties" in your dashboard, or I can connect you with one of our agents. What would you prefer?';
        } elseif (str_contains($input, 'contact') || str_contains($input, 'support') || str_contains($input, 'help')) {
            return 'You can reach our support team at support@emoh.com or call us at +1 (555) 123-4567. We\'re here Monday to Friday, 9 AM - 6 PM. How else can I assist you?';
        } elseif (str_contains($input, 'account') || str_contains($input, 'profile')) {
            return 'To update your account information, click on the "Settings" card in your dashboard. You can edit your profile, change your password, and manage preferences there.';
        } elseif (str_contains($input, 'verify') || str_contains($input, 'email')) {
            return 'To verify your email, please check your inbox for a verification link. If you didn\'t receive it, you can request a new verification email from the dashboard.';
        } elseif (str_contains($input, 'payment') || str_contains($input, 'rent')) {
            return 'For payment-related questions, please visit the "My Rentals" section where you can view payment schedules, make payments, and download receipts.';
        } elseif (str_contains($input, 'hi') || str_contains($input, 'hello') || str_contains($input, 'hey')) {
            return 'Hello! How can I assist you today? I can help with property inquiries, account management, payments, and general support questions.';
        } elseif (str_contains($input, 'thank')) {
            return 'You\'re welcome! Is there anything else I can help you with?';
        } else {
            return 'I understand you need help. Could you please provide more details? You can ask me about properties, account settings, payments, or general inquiries. I\'m here to help!';
        }
    }
}
