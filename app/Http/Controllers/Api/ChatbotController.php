<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ChatHistory;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $service)
    {
    }

    public function stream(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        $sessionId = $request->input('session_id') ?: Str::uuid()->toString();
        $client = $request->user('client');
        $message = $request->string('message');

        return $this->service->streamResponse($sessionId, $client, $message);
    }

    public function history(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = (int) ($request->input('limit') ?: 50);
        $items = ChatHistory::query()
            ->where('session_id', $request->string('session_id'))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get(['id','user_message','assistant_response','created_at']);

        $messages = [];
        foreach ($items as $row) {
            $messages[] = [
                'id' => 'u-' . $row->id,
                'text' => $row->user_message,
                'sender' => 'user',
                'timestamp' => $row->created_at?->toISOString(),
            ];
            if ($row->assistant_response) {
                $messages[] = [
                    'id' => 'a-' . $row->id,
                    'text' => $row->assistant_response,
                    'sender' => 'bot',
                    'timestamp' => $row->created_at?->toISOString(),
                ];
            }
        }

        return response()->json(['messages' => $messages]);
    }
}


