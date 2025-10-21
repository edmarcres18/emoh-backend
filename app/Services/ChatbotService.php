<?php

namespace App\Services;

use App\Models\ChatHistory;
use App\Models\Client;
use App\Models\Property;
use App\Models\Rented;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotService
{
    public function streamResponse(string $sessionId, ?Client $client, string $message): StreamedResponse
    {
        $model = config('services.openrouter.model', env('OPENROUTER_MODEL'));
        $baseUrl = config('services.openrouter.base_url', env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'));
        $apiKey = config('services.openrouter.key', env('OPENROUTER_API_KEY'));

        $systemPrompt = $this->buildSystemPrompt($client);

        $requestBody = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'stream' => true,
            'temperature' => 0.2,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name', 'EMOH'),
        ];

        $history = ChatHistory::create([
            'client_id' => $client?->id,
            'session_id' => $sessionId,
            'user_message' => $message,
            'assistant_response' => '',
            'metadata' => [
                'model' => $model,
            ],
        ]);

        return response()->stream(function () use ($baseUrl, $requestBody, $headers, $history) {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'stream' => true,
                    'timeout' => 0,
                ])
                ->post($baseUrl . '/chat/completions', $requestBody);

            $assistantText = '';

            $body = $response->toPsrResponse()->getBody();
            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '' || $chunk === false) {
                    usleep(10000);
                    continue;
                }

                echo $chunk;
                @ob_flush();
                flush();

                // Try to accumulate text for persistence when "delta" tokens arrive
                // OpenRouter streams Server-Sent Events with JSON lines prefixed by "data:"
                foreach (preg_split("/\r?\n/", $chunk) as $line) {
                    $line = trim($line);
                    if ($line === '' || !str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $payload = substr($line, 5);
                    if ($payload === '[DONE]') {
                        continue;
                    }
                    $json = json_decode($payload, true);
                    if (!is_array($json)) {
                        continue;
                    }
                    $delta = $json['choices'][0]['delta']['content'] ?? '';
                    if (is_string($delta)) {
                        $assistantText .= $delta;
                    }
                }
            }

            // Save the accumulated response
            $history->assistant_response = $assistantText;
            $history->save();
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function buildSystemPrompt(?Client $client): string
    {
        $lines = [];
        $lines[] = 'You are EMOH assistant. Answer in plain text only.';
        $lines[] = 'Use ONLY the following knowledge when relevant.';

        // Client context
        if ($client) {
            $activeRentals = $client->activeRentals()->with('property')->get();
            $lines[] = 'Client: ' . ($client->name ?? ('ID ' . $client->id));
            $lines[] = 'Active rentals: ' . $activeRentals->count();
            foreach ($activeRentals as $r) {
                $lines[] = sprintf(
                    'Rental #%d: property=%s monthly=%.2f end=%s status=%s',
                    $r->id,
                    $r->property?->property_name ?? ('ID ' . $r->property_id),
                    (float) $r->monthly_rent,
                    $r->end_date ?? 'N/A',
                    $r->status
                );
            }
        }

        // Property highlights
        $featured = Property::query()->where('is_featured', true)->limit(5)->get(['id','property_name','estimated_monthly','status']);
        if ($featured->isNotEmpty()) {
            $lines[] = 'Featured properties:';
            foreach ($featured as $p) {
                $lines[] = sprintf(
                    '- %s (ID %d): ₱%.2f, status=%s',
                    $p->property_name,
                    $p->id,
                    (float) $p->estimated_monthly,
                    $p->status
                );
            }
        }

        // Rental stats
        $activeCount = Rented::query()->active()->count();
        $lines[] = 'Active rentals count: ' . $activeCount;

        $lines[] = 'If unsure or out-of-scope, ask for clarification.';

        return implode("\n", $lines);
    }
}


