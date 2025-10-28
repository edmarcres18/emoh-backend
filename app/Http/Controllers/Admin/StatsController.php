<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Return counters for admin sidebar badges.
     * - clients_new_last_2_days: Clients created within the last 2 days
     * - inquiries_unviewed: Inquiries where viewed_at is null
     */
    public function counters(Request $request): JsonResponse
    {
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $clientsNewLast2Days = Client::where('created_at', '>=', now()->subDays(2))->count();
        $inquiriesUnviewed = Inquiry::whereNull('viewed_at')->count();

        return response()->json([
            'clients_new_last_2_days' => $clientsNewLast2Days,
            'inquiries_unviewed' => $inquiriesUnviewed,
        ]);
    }
}