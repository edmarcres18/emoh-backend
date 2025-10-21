<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Property;
use App\Models\Rented;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected int $maxTokens;
    protected float $temperature;
    protected string $systemPrompt;

    public function __construct()
    {
        $this->apiKey = config('openrouter.api_key');
        $this->baseUrl = config('openrouter.base_url');
        $this->model = config('openrouter.model');
        $this->timeout = config('openrouter.timeout');
        $this->maxTokens = config('openrouter.max_tokens');
        $this->temperature = config('openrouter.temperature');
        $this->systemPrompt = config('openrouter.system_prompt');
    }

    /**
     * Generate knowledge base context from models.
     */
    public function generateKnowledgeBase(?int $clientId = null): string
    {
        $context = [];

        try {
            // Get properties data
            $properties = Property::with(['category', 'location', 'rentals'])
                ->get()
                ->map(function ($property) {
                    return [
                        'id' => $property->id,
                        'name' => $property->property_name,
                        'category' => $property->category->category_name ?? 'N/A',
                        'location' => $property->location->location_name ?? 'N/A',
                        'monthly_rent' => $property->estimated_monthly,
                        'lot_area' => $property->lot_area,
                        'floor_area' => $property->floor_area,
                        'status' => $property->status,
                        'is_featured' => $property->is_featured,
                        'details' => $property->details,
                        'is_rented' => $property->isRented(),
                    ];
                });

            $context[] = "AVAILABLE PROPERTIES:\n" . json_encode($properties->toArray(), JSON_PRETTY_PRINT);

            // Get rental statistics
            $totalRentals = Rented::count();
            $activeRentals = Rented::where('status', 'active')->count();
            $expiredRentals = Rented::where('status', 'expired')->count();

            $context[] = "\nRENTAL STATISTICS:";
            $context[] = "Total Rentals: {$totalRentals}";
            $context[] = "Active Rentals: {$activeRentals}";
            $context[] = "Expired Rentals: {$expiredRentals}";

            // If client is authenticated, include their specific data
            if ($clientId) {
                $client = Client::with(['rentals.property.category', 'rentals.property.location'])
                    ->find($clientId);

                if ($client) {
                    $clientRentals = $client->rentals->map(function ($rental) {
                        return [
                            'property' => $rental->property->property_name ?? 'N/A',
                            'location' => $rental->property->location->location_name ?? 'N/A',
                            'monthly_rent' => $rental->monthly_rent,
                            'start_date' => $rental->start_date->format('Y-m-d'),
                            'end_date' => $rental->end_date ? $rental->end_date->format('Y-m-d') : 'Ongoing',
                            'status' => $rental->status,
                            'remarks' => $rental->remarks,
                        ];
                    });

                    $context[] = "\nYOUR RENTAL INFORMATION:";
                    $context[] = "Name: {$client->name}";
                    $context[] = "Email: {$client->email}";
                    $context[] = "Total Rentals: {$client->rentals->count()}";
                    $context[] = "Active Rentals: {$client->activeRentals->count()}";
                    $context[] = "\nYOUR RENTAL HISTORY:\n" . json_encode($clientRentals->toArray(), JSON_PRETTY_PRINT);
                }
            }

            // Get property categories summary
            $categories = Property::with('category')
                ->get()
                ->groupBy('category.category_name')
                ->map(function ($group, $categoryName) {
                    return [
                        'category' => $categoryName ?: 'Uncategorized',
                        'count' => $group->count(),
                        'available' => $group->where('status', 'Available')->count(),
                        'rented' => $group->where('status', 'Rented')->count(),
                    ];
                });

            $context[] = "\nPROPERTY CATEGORIES:\n" . json_encode($categories->values()->toArray(), JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            Log::error('Error generating knowledge base: ' . $e->getMessage());
            $context[] = "Error loading property data.";
        }

        return implode("\n", $context);
    }

    /**
     * Send a chat request to OpenRouter API.
     */
    public function chat(array $messages, ?int $clientId = null): array
    {
        try {
            // Generate knowledge base context
            $knowledgeBase = $this->generateKnowledgeBase($clientId);

            // Prepare system message with knowledge base
            $systemMessage = [
                'role' => 'system',
                'content' => $this->systemPrompt . "\n\nKNOWLEDGE BASE:\n" . $knowledgeBase,
            ];

            // Combine system message with conversation history
            $fullMessages = array_merge([$systemMessage], $messages);

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $fullMessages,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'message' => $data['choices'][0]['message']['content'] ?? '',
                    'model' => $data['model'] ?? $this->model,
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('OpenRouter API error: ' . $response->body());
            
            return [
                'success' => false,
                'error' => 'Failed to get response from AI service',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('OpenRouter service exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'An error occurred while processing your request',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a streaming chat request to OpenRouter API.
     */
    public function chatStream(array $messages, ?int $clientId = null): \Generator
    {
        try {
            // Generate knowledge base context
            $knowledgeBase = $this->generateKnowledgeBase($clientId);

            // Prepare system message with knowledge base
            $systemMessage = [
                'role' => 'system',
                'content' => $this->systemPrompt . "\n\nKNOWLEDGE BASE:\n" . $knowledgeBase,
            ];

            // Combine system message with conversation history
            $fullMessages = array_merge([$systemMessage], $messages);

            // Make streaming API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
            ])
            ->timeout($this->timeout)
            ->withOptions(['stream' => true])
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $fullMessages,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'stream' => true,
            ]);

            if ($response->successful()) {
                $body = $response->getBody();
                while (!$body->eof()) {
                    $line = $this->readLine($body);
                    if (empty($line)) {
                        continue;
                    }

                    if (str_starts_with($line, 'data: ')) {
                        $data = substr($line, 6);
                        
                        if ($data === '[DONE]') {
                            break;
                        }

                        $decoded = json_decode($data, true);
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            yield $decoded['choices'][0]['delta']['content'];
                        }
                    }
                }
            } else {
                yield json_encode([
                    'error' => 'Failed to get response from AI service',
                    'details' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OpenRouter streaming exception: ' . $e->getMessage());
            yield json_encode([
                'error' => 'An error occurred while processing your request',
                'details' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Read a line from stream.
     */
    protected function readLine($stream): string
    {
        $buffer = '';
        while (!$stream->eof()) {
            $char = $stream->read(1);
            $buffer .= $char;
            if ($char === "\n") {
                break;
            }
        }
        return trim($buffer);
    }

    /**
     * Validate API configuration.
     */
    public function validateConfig(): array
    {
        if (empty($this->apiKey)) {
            return [
                'valid' => false,
                'error' => 'OpenRouter API key is not configured',
            ];
        }

        if (empty($this->baseUrl)) {
            return [
                'valid' => false,
                'error' => 'OpenRouter base URL is not configured',
            ];
        }

        if (empty($this->model)) {
            return [
                'valid' => false,
                'error' => 'OpenRouter model is not configured',
            ];
        }

        return [
            'valid' => true,
            'config' => [
                'model' => $this->model,
                'base_url' => $this->baseUrl,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ],
        ];
    }
}
