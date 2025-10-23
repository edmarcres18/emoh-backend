<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestInquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestInquiryAdminController extends Controller
{
    /**
     * Display the guest inquiries dashboard
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $subject = $request->input('subject', 'all');
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);

        $query = GuestInquiry::query()->orderBy('created_at', 'desc');

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by subject
        if ($subject !== 'all') {
            $query->where('subject', $subject);
        }

        // Search functionality
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $inquiries = $query->paginate($perPage)->withQueryString();

        // Get statistics
        $stats = [
            'total' => GuestInquiry::count(),
            'pending' => GuestInquiry::where('status', 'pending')->count(),
            'in_progress' => GuestInquiry::where('status', 'in_progress')->count(),
            'resolved' => GuestInquiry::where('status', 'resolved')->count(),
            'closed' => GuestInquiry::where('status', 'closed')->count(),
        ];

        // Get recent inquiries count (last 7 days)
        $recentCount = GuestInquiry::where('created_at', '>=', now()->subDays(7))->count();

        return Inertia::render('Admin/GuestInquiries/Index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'recentCount' => $recentCount,
            'filters' => [
                'status' => $status,
                'subject' => $subject,
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show a specific inquiry
     *
     * @param GuestInquiry $inquiry
     * @return \Inertia\Response
     */
    public function show(GuestInquiry $inquiry)
    {
        $inquiry->load('resolver');

        return Inertia::render('Admin/GuestInquiries/Show', [
            'inquiry' => $inquiry,
        ]);
    }

    /**
     * Update inquiry status
     *
     * @param Request $request
     * @param GuestInquiry $inquiry
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, GuestInquiry $inquiry)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $data = [
            'status' => $request->input('status'),
        ];

        if ($request->filled('admin_notes')) {
            $data['admin_notes'] = $request->input('admin_notes');
        }

        if ($request->input('status') === 'resolved' && $inquiry->status !== 'resolved') {
            $data['resolved_at'] = now();
            $data['resolved_by'] = auth()->id();
        }

        $inquiry->update($data);

        return redirect()->back()->with('success', 'Inquiry status updated successfully');
    }

    /**
     * Delete an inquiry
     *
     * @param GuestInquiry $inquiry
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(GuestInquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('admin.guest-inquiries.index')
            ->with('success', 'Inquiry deleted successfully');
    }

    /**
     * Get inquiry statistics (API endpoint for Vue components)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $stats = [
            'total' => GuestInquiry::count(),
            'pending' => GuestInquiry::where('status', 'pending')->count(),
            'in_progress' => GuestInquiry::where('status', 'in_progress')->count(),
            'resolved' => GuestInquiry::where('status', 'resolved')->count(),
            'closed' => GuestInquiry::where('status', 'closed')->count(),
            'recent_7_days' => GuestInquiry::where('created_at', '>=', now()->subDays(7))->count(),
            'recent_30_days' => GuestInquiry::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Get inquiries by subject
        $bySubject = GuestInquiry::selectRaw('subject, count(*) as count')
            ->groupBy('subject')
            ->pluck('count', 'subject')
            ->toArray();

        // Get recent inquiries
        $recentInquiries = GuestInquiry::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'subject', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'by_subject' => $bySubject,
                'recent_inquiries' => $recentInquiries,
            ],
        ]);
    }
}
