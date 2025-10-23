<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestInquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestInquiryAdminController extends Controller
{
    /**
     * Display a listing of guest inquiries.
     */
    public function index(Request $request)
    {
        $query = GuestInquiry::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Get inquiries with pagination
        $inquiries = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => GuestInquiry::count(),
            'pending' => GuestInquiry::where('status', 'pending')->count(),
            'read' => GuestInquiry::where('status', 'read')->count(),
            'replied' => GuestInquiry::where('status', 'replied')->count(),
            'archived' => GuestInquiry::where('status', 'archived')->count(),
        ];

        return Inertia::render('Admin/GuestInquiries/Index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Display the specified inquiry.
     */
    public function show(GuestInquiry $guestInquiry)
    {
        // Mark as read if it's pending
        if ($guestInquiry->status === 'pending') {
            $guestInquiry->markAsRead();
        }

        return Inertia::render('Admin/GuestInquiries/Show', [
            'inquiry' => $guestInquiry,
        ]);
    }

    /**
     * Update the status of the specified inquiry.
     */
    public function updateStatus(Request $request, GuestInquiry $guestInquiry)
    {
        $request->validate([
            'status' => 'required|in:pending,read,replied,archived',
        ]);

        $guestInquiry->update([
            'status' => $request->input('status'),
        ]);

        if ($request->input('status') === 'replied') {
            $guestInquiry->markAsReplied();
        }

        return back()->with('success', 'Inquiry status updated successfully.');
    }

    /**
     * Update admin notes for the specified inquiry.
     */
    public function updateNotes(Request $request, GuestInquiry $guestInquiry)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $guestInquiry->update([
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return back()->with('success', 'Admin notes updated successfully.');
    }

    /**
     * Remove the specified inquiry from storage.
     */
    public function destroy(GuestInquiry $guestInquiry)
    {
        $guestInquiry->delete();

        return redirect()->route('admin.guest-inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }
}
