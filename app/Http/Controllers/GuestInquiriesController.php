<?php

namespace App\Http\Controllers;

use App\Models\GuestInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestInquiriesController extends Controller
{
    /**
     * Display a listing of guest inquiries.
     */
    public function index(Request $request): Response
    {
        $sortBy = $request->get('sort', 'latest');
        $statusFilter = $request->get('status', 'all');
        $perPage = $request->get('per_page', 10);
        
        $inquiries = GuestInquiry::query()
            ->with('responder:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->when($sortBy === 'latest', function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->when($sortBy === 'oldest', function ($query) {
                $query->orderBy('created_at', 'asc');
            })
            ->when($sortBy === 'email_asc', function ($query) {
                $query->orderBy('email', 'asc');
            })
            ->when($sortBy === 'email_desc', function ($query) {
                $query->orderBy('email', 'desc');
            })
            ->when($sortBy === 'status', function ($query) {
                $query->orderBy('status', 'asc');
            })
            ->paginate($perPage)
            ->withQueryString();

        // Get statistics
        $stats = [
            'total' => GuestInquiry::count(),
            'pending' => GuestInquiry::where('status', 'pending')->count(),
            'responded' => GuestInquiry::where('status', 'responded')->count(),
            'spam' => GuestInquiry::where('status', 'spam')->count(),
            'today' => GuestInquiry::whereDate('created_at', today())->count(),
            'this_week' => GuestInquiry::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => GuestInquiry::whereMonth('created_at', now()->month)->count(),
        ];

        // Ensure filters are always passed
        $filters = $request->only(['search', 'sort', 'status', 'per_page']);
        if (!isset($filters['sort'])) {
            $filters['sort'] = 'latest';
        }
        if (!isset($filters['status'])) {
            $filters['status'] = 'all';
        }
        if (!isset($filters['per_page'])) {
            $filters['per_page'] = 10;
        }

        return Inertia::render('GuestInquiries/Index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Display the specified guest inquiry.
     */
    public function show(string $id): Response
    {
        $inquiry = GuestInquiry::with('responder:id,name,email')
            ->findOrFail($id);

        return Inertia::render('GuestInquiries/Show', [
            'inquiry' => $inquiry,
        ]);
    }

    /**
     * Update the status of the specified inquiry.
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,responded,spam',
        ]);

        try {
            $inquiry = GuestInquiry::findOrFail($id);
            
            if ($request->status === 'responded') {
                $inquiry->markAsResponded($request->user()->id ?? null);
            } else {
                $inquiry->status = $request->status;
                $inquiry->save();
            }

            return redirect()->back()
                ->with('success', 'Inquiry status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update inquiry status. Please try again.');
        }
    }

    /**
     * Remove the specified inquiry from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $inquiry = GuestInquiry::findOrFail($id);
            $inquiry->delete();

            return redirect()->route('guest-inquiries.index')
                ->with('success', 'Inquiry deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete inquiry. Please try again.');
        }
    }
}
