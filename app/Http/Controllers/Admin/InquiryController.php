<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    /**
     * Display a listing of inquiries (Inertia page).
     */
    public function indexPage(Request $request): Response
    {
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to view inquiries');
        }

        $query = Inquiry::with(['client', 'property', 'property.category', 'property.location']);

        if ($request->has('status') && in_array($request->status, ['new', 'contacted', 'closed'])) {
            $query->where('status', $request->status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $inquiries = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        $stats = [
            'total' => Inquiry::count(),
            'new' => Inquiry::where('status', 'new')->count(),
            'contacted' => Inquiry::where('status', 'contacted')->count(),
            'closed' => Inquiry::where('status', 'closed')->count(),
        ];

        return Inertia::render('Admin/Inquiries/Index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Update inquiry status (AJAX/API).
     */
    public function updateStatus(Inquiry $inquiry, Request $request): JsonResponse
    {
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);
        $inquiry->update(['status' => $validated['status']]);
        return response()->json(['success' => true, 'message' => 'Status updated', 'data' => $inquiry]);
    }
}