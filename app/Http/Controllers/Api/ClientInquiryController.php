<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Inquiry;
use App\Models\Property;

class ClientInquiryController extends Controller
{
    /**
     * Store a new inquiry from authenticated client.
     */
    public function store(Request $request): JsonResponse
    {
        $client = auth('client')->user();
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $property = Property::findOrFail($validated['property_id']);

        $inquiry = Inquiry::create([
            'client_id' => $client->id,
            'property_id' => $property->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'message' => $validated['message'],
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry submitted successfully',
            'data' => $inquiry,
        ], 201);
    }

    /**
     * List inquiries for authenticated client.
     */
    public function index(Request $request): JsonResponse
    {
        $client = auth('client')->user();
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $inquiries = Inquiry::with(['property'])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Inquiries retrieved successfully',
            'data' => [
                'items' => $inquiries->items(),
                'pagination' => [
                    'current_page' => $inquiries->currentPage(),
                    'per_page' => $inquiries->perPage(),
                    'total' => $inquiries->total(),
                    'last_page' => $inquiries->lastPage(),
                    'from' => $inquiries->firstItem(),
                    'to' => $inquiries->lastItem(),
                ],
            ]
        ], 200);
    }
}