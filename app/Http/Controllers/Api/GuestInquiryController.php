<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class GuestInquiryController extends Controller
{
    /**
     * Submit a guest inquiry from the contact form
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            // Get IP address for rate limiting
            $ipAddress = $request->ip();
            $rateLimitKey = 'guest-inquiry:' . $ipAddress;
            
            // Rate limiting: 3 attempts per hour
            if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                $minutes = ceil($seconds / 60);
                
                return response()->json([
                    'success' => false,
                    'message' => "Too many inquiry attempts. Please try again in {$minutes} minute(s).",
                    'retry_after' => $seconds,
                ], 429);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|in:rental,lease,general,support',
                'message' => 'required|string|min:10|max:5000',
                'captcha_verified' => 'required|boolean',
            ], [
                'first_name.required' => 'First name is required',
                'first_name.max' => 'First name must not exceed 255 characters',
                'last_name.required' => 'Last name is required',
                'last_name.max' => 'Last name must not exceed 255 characters',
                'email.required' => 'Email address is required',
                'email.email' => 'Please provide a valid email address',
                'email.max' => 'Email must not exceed 255 characters',
                'subject.required' => 'Please select a subject',
                'subject.in' => 'Invalid subject selected',
                'message.required' => 'Message is required',
                'message.min' => 'Message must be at least 10 characters',
                'message.max' => 'Message must not exceed 5000 characters',
                'captcha_verified.required' => 'CAPTCHA verification is required',
                'captcha_verified.boolean' => 'Invalid CAPTCHA verification format',
            ]);

            if ($validator->fails()) {
                // Increment rate limiter for failed validation
                RateLimiter::hit($rateLimitKey, 3600); // 1 hour decay

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verify CAPTCHA was completed
            if (!$request->input('captcha_verified')) {
                // Increment rate limiter for failed CAPTCHA
                RateLimiter::hit($rateLimitKey, 3600);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete the CAPTCHA verification',
                ], 422);
            }

            // Create the guest inquiry
            $inquiry = GuestInquiry::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'captcha_verified' => true,
                'status' => 'pending',
            ]);

            // Increment rate limiter for successful submission
            RateLimiter::hit($rateLimitKey, 3600);

            // Log successful submission
            Log::info('Guest inquiry submitted', [
                'inquiry_id' => $inquiry->id,
                'email' => $inquiry->email,
                'subject' => $inquiry->subject,
                'ip_address' => $ipAddress,
            ]);

            // TODO: Send email notification to admin
            // TODO: Send auto-response email to user

            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us! We\'ll get back to you within 24-48 hours.',
                'data' => [
                    'inquiry_id' => $inquiry->id,
                ],
            ], 201);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error submitting guest inquiry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your inquiry. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get inquiry statistics (admin only)
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => GuestInquiry::count(),
                'pending' => GuestInquiry::where('status', 'pending')->count(),
                'responded' => GuestInquiry::where('status', 'responded')->count(),
                'spam' => GuestInquiry::where('status', 'spam')->count(),
                'today' => GuestInquiry::whereDate('created_at', today())->count(),
                'this_week' => GuestInquiry::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => GuestInquiry::whereMonth('created_at', now()->month)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching inquiry stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    /**
     * Get all inquiries (admin only)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');

            $query = GuestInquiry::orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $inquiries = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $inquiries,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching inquiries', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inquiries',
            ], 500);
        }
    }

    /**
     * Get single inquiry (admin only)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $inquiry = GuestInquiry::with('responder')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $inquiry,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found',
            ], 404);
        }
    }

    /**
     * Update inquiry status (admin only)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,responded,spam',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $inquiry = GuestInquiry::findOrFail($id);
            
            if ($request->status === 'responded') {
                $inquiry->markAsResponded($request->user()->id ?? null);
            } else {
                $inquiry->status = $request->status;
                $inquiry->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Inquiry status updated successfully',
                'data' => $inquiry,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inquiry status',
            ], 500);
        }
    }

    /**
     * Delete inquiry (admin only)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $inquiry = GuestInquiry::findOrFail($id);
            $inquiry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inquiry deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inquiry',
            ], 500);
        }
    }
}
