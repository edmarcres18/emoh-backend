<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ChatbotAIService
 * 
 * Provides AI-powered chatbot responses using client data analysis
 * Supports multiple AI providers:
 * - OpenRouter (DeepSeek - FREE, recommended)
 * - OpenAI (GPT models - Paid)
 * - Rule-based fallback (FREE, built-in)
 */
class ChatbotAIService
{
    private $client;
    private $apiKey;
    private $apiModel;
    private $apiProvider;
    private $apiBaseUrl;

    public function __construct()
    {
        // Support both OpenRouter (DeepSeek) and OpenAI
        $this->apiProvider = env('AI_PROVIDER', 'openrouter'); // 'openrouter' or 'openai'
        
        if ($this->apiProvider === 'openrouter') {
            $this->apiKey = env('OPENROUTER_API_KEY');
            $this->apiModel = env('OPENROUTER_MODEL', 'deepseek/deepseek-chat'); // Free DeepSeek model
            $this->apiBaseUrl = 'https://openrouter.ai/api/v1/chat/completions';
        } else {
            // OpenAI fallback
            $this->apiKey = env('OPENAI_API_KEY');
            $this->apiModel = env('OPENAI_MODEL', 'gpt-3.5-turbo');
            $this->apiBaseUrl = 'https://api.openai.com/v1/chat/completions';
        }
    }

    /**
     * Generate AI response based on user message and client context
     */
    public function generateResponse(string $userMessage, Client $client): array
    {
        $this->client = $client;

        // Get client context for AI
        $context = $this->buildClientContext();

        // Check if AI API is configured (OpenRouter or OpenAI)
        if ($this->apiKey) {
            return $this->getAIResponse($userMessage, $context);
        }

        // Fallback to rule-based responses
        return $this->getRuleBasedResponse($userMessage, $context);
    }

    /**
     * Build comprehensive client context for AI analysis
     */
    private function buildClientContext(): array
    {
        $client = $this->client;

        // Load client rentals with property details
        $rentals = $client->rentalHistory()->with([
            'property.category',
            'property.location'
        ])->get();

        $activeRentals = $rentals->where('status', 'active');
        $totalRentals = $rentals->count();

        // Prepare rental summary
        $rentalsSummary = $activeRentals->map(function ($rental) {
            return [
                'property_title' => $rental->property->title ?? 'Unknown',
                'property_type' => $rental->property->category->name ?? 'Unknown',
                'location' => $rental->property->location->name ?? 'Unknown',
                'monthly_rent' => $rental->monthly_rent,
                'start_date' => $rental->start_date->format('Y-m-d'),
                'end_date' => $rental->end_date?->format('Y-m-d') ?? 'Ongoing',
                'remaining_days' => $rental->remaining_days,
                'status' => $rental->status,
                'remarks' => $rental->remarks,
            ];
        })->toArray();

        return [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'email_verified' => $client->email_verified_at ? true : false,
            'account_status' => $client->is_active ? 'active' : 'inactive',
            'member_since' => $client->created_at->format('Y-m-d'),
            'total_rentals' => $totalRentals,
            'active_rentals' => $activeRentals->count(),
            'rentals' => $rentalsSummary,
        ];
    }

    /**
     * Get response from AI API (OpenRouter or OpenAI)
     */
    private function getAIResponse(string $userMessage, array $context): array
    {
        try {
            $systemPrompt = $this->buildSystemPrompt($context);

            // Prepare headers based on provider
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            // OpenRouter requires additional headers
            if ($this->apiProvider === 'openrouter') {
                $headers['HTTP-Referer'] = env('APP_URL', 'http://localhost');
                $headers['X-Title'] = 'EMOH Property Management';
            }

            // Prepare request body
            $requestBody = [
                'model' => $this->apiModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ];

            // Make API request with proper SSL handling
            $httpClient = Http::withHeaders($headers)
                ->timeout(30);
            
            // In development, you might need to disable SSL verification
            // Remove this in production for security
            if (env('APP_ENV') === 'local' && env('DISABLE_SSL_VERIFY', false)) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            $response = $httpClient->post($this->apiBaseUrl, $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? '';

                return [
                    'response' => $aiResponse,
                    'source' => $this->apiProvider,
                    'metadata' => [
                        'provider' => $this->apiProvider,
                        'model' => $this->apiModel,
                        'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    ],
                ];
            }

            Log::warning($this->apiProvider . ' API request failed', [
                'provider' => $this->apiProvider,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500), // Limit log size
                'url' => $this->apiBaseUrl,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error($this->apiProvider . ' connection error: ' . $e->getMessage(), [
                'provider' => $this->apiProvider,
                'message' => 'Network or SSL connection failed',
            ]);
        } catch (\Exception $e) {
            Log::error($this->apiProvider . ' API error: ' . $e->getMessage(), [
                'provider' => $this->apiProvider,
                'error_type' => get_class($e),
            ]);
        }

        // Fallback to rule-based if AI API fails
        return $this->getRuleBasedResponse($userMessage, $context);
    }

    /**
     * Build system prompt for AI with client context
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = "You are EMOH 24/7 AI support, a helpful customer support assistant for EMOH property rental platform. ";
        $prompt .= "You provide 24/7 assistance to clients. You are currently assisting {$context['client_name']} (Client ID: {$context['client_id']}). ";
        $prompt .= "\n\nClient Information:\n";
        $prompt .= "- Name: {$context['client_name']}\n";
        $prompt .= "- Email: {$context['client_email']}\n";
        $prompt .= "- Phone: " . ($context['client_phone'] ?? 'Not provided') . "\n";
        $prompt .= "- Email Verified: " . ($context['email_verified'] ? 'Yes' : 'No') . "\n";
        $prompt .= "- Account Status: {$context['account_status']}\n";
        $prompt .= "- Member Since: {$context['member_since']}\n";
        $prompt .= "- Total Rentals: {$context['total_rentals']}\n";
        $prompt .= "- Active Rentals: {$context['active_rentals']}\n";

        if (!empty($context['rentals'])) {
            $prompt .= "\nActive Rental Properties:\n";
            foreach ($context['rentals'] as $index => $rental) {
                $prompt .= ($index + 1) . ". {$rental['property_title']} ({$rental['property_type']})\n";
                $prompt .= "   Location: {$rental['location']}\n";
                $prompt .= "   Monthly Rent: ₱" . number_format($rental['monthly_rent'], 2) . "\n";
                $prompt .= "   Contract: {$rental['start_date']} to {$rental['end_date']}\n";
                $prompt .= "   Remaining Days: " . ($rental['remaining_days'] ?? 'N/A') . "\n";
                $prompt .= "   Remarks: {$rental['remarks']}\n";
            }
        }

        $prompt .= "\nYour role is to:\n";
        $prompt .= "- Answer questions about their rentals and properties\n";
        $prompt .= "- Provide information about payments, contracts, and due dates\n";
        $prompt .= "- Help with account-related inquiries\n";
        $prompt .= "- Guide them to browse more properties\n";
        $prompt .= "- Offer support contact information when needed\n";
        $prompt .= "\nBe professional, friendly, and helpful. Use the client data provided to give personalized responses.";

        return $prompt;
    }

    /**
     * Rule-based response system (fallback)
     */
    private function getRuleBasedResponse(string $userMessage, array $context): array
    {
        $message = strtolower($userMessage);
        $response = '';

        // Rental-related queries
        if (preg_match('/\b(rental|rent|property|properties|lease)\b/i', $message)) {
            if ($context['active_rentals'] > 0) {
                $response = "You currently have {$context['active_rentals']} active rental(s). ";
                
                foreach ($context['rentals'] as $rental) {
                    $response .= "Your rental at {$rental['property_title']} in {$rental['location']} ";
                    $response .= "has a monthly rent of ₱" . number_format($rental['monthly_rent'], 2) . ". ";
                    
                    if ($rental['remaining_days']) {
                        $response .= "You have {$rental['remaining_days']} days remaining on your contract. ";
                    }
                    
                    $response .= "Status: {$rental['remarks']}. ";
                }
                
                $response .= "You can view more details in your 'My Rentals' section.";
            } else {
                $response = "You don't have any active rentals at the moment. Would you like to browse our available properties?";
            }
        }
        // Payment queries
        elseif (preg_match('/\b(payment|pay|due|bill|invoice)\b/i', $message)) {
            if ($context['active_rentals'] > 0) {
                $response = "For payment information, please check your 'My Rentals' section where you can view payment schedules and make payments. ";
                
                foreach ($context['rentals'] as $rental) {
                    if (stripos($rental['remarks'], 'due') !== false || stripos($rental['remarks'], 'overdue') !== false) {
                        $response .= "Note: Your rental at {$rental['property_title']} is {$rental['remarks']}. ";
                    }
                }
            } else {
                $response = "You don't have any active rentals requiring payment at this time.";
            }
        }
        // Account/profile queries
        elseif (preg_match('/\b(account|profile|email|verify|update)\b/i', $message)) {
            $response = "Your account information:\n";
            $response .= "- Status: " . ucfirst($context['account_status']) . "\n";
            $response .= "- Email: {$context['client_email']} ";
            
            if (!$context['email_verified']) {
                $response .= "(Not verified - please verify your email to access all features)\n";
            } else {
                $response .= "(Verified)\n";
            }
            
            $response .= "- Member since: {$context['member_since']}\n\n";
            $response .= "To update your profile, click on the 'Settings' card in your dashboard.";
        }
        // Contact/support queries
        elseif (preg_match('/\b(contact|support|help|call|email|phone)\b/i', $message)) {
            $response = "You can reach our support team at:\n";
            $response .= "📧 Email: support@emoh.com\n";
            $response .= "📞 Phone: +1 (555) 123-4567\n";
            $response .= "🕐 Hours: Monday to Friday, 9 AM - 6 PM\n\n";
            $response .= "For immediate assistance with your rental at ";
            
            if ($context['active_rentals'] > 0 && !empty($context['rentals'])) {
                $response .= $context['rentals'][0]['property_title'] . ", please include your rental details when contacting us.";
            } else {
                $response .= "our properties, feel free to reach out!";
            }
        }
        // Greeting
        elseif (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/i', $message)) {
            $response = "Hello {$context['client_name']}! 👋 How can I assist you today? ";
            
            if ($context['active_rentals'] > 0) {
                $response .= "I can help you with information about your {$context['active_rentals']} active rental(s), ";
            }
            
            $response .= "payment inquiries, account settings, or finding new properties.";
        }
        // Thank you
        elseif (preg_match('/\b(thank|thanks)\b/i', $message)) {
            $response = "You're welcome, {$context['client_name']}! Is there anything else I can help you with?";
        }
        // Browse properties
        elseif (preg_match('/\b(browse|find|search|available|looking for)\b/i', $message)) {
            $response = "You can browse our available properties by clicking 'Browse Properties' in your dashboard. ";
            $response .= "We have a wide selection of quality properties for rent and lease. ";
            $response .= "Would you like me to help you find something specific?";
        }
        // Default response
        else {
            $response = "I understand you need assistance. Could you please provide more details? ";
            $response .= "I can help you with:\n";
            $response .= "- Your rental properties and contracts\n";
            $response .= "- Payment information\n";
            $response .= "- Account settings\n";
            $response .= "- Browsing available properties\n";
            $response .= "- General inquiries\n\n";
            $response .= "What would you like to know more about?";
        }

        return [
            'response' => $response,
            'source' => 'rule-based',
            'metadata' => [
                'context_used' => array_keys($context),
            ],
        ];
    }

    /**
     * Analyze user query intent
     */
    public function analyzeIntent(string $message): string
    {
        $message = strtolower($message);

        if (preg_match('/\b(rental|rent|property|lease)\b/i', $message)) {
            return 'rental_inquiry';
        } elseif (preg_match('/\b(payment|pay|due|bill)\b/i', $message)) {
            return 'payment_inquiry';
        } elseif (preg_match('/\b(account|profile|email|verify)\b/i', $message)) {
            return 'account_inquiry';
        } elseif (preg_match('/\b(contact|support|help)\b/i', $message)) {
            return 'support_request';
        } elseif (preg_match('/\b(browse|find|search|available)\b/i', $message)) {
            return 'property_search';
        } elseif (preg_match('/\b(hi|hello|hey)\b/i', $message)) {
            return 'greeting';
        } else {
            return 'general_inquiry';
        }
    }
}
