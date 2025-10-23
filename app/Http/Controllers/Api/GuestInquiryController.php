<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestInquiry;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GuestInquiryController extends Controller
{
    /**
     * Generate a new CAPTCHA challenge.
     */
    public function getCaptcha()
    {
        try {
            $captcha = CaptchaService::generate();
            
            return response()->json([
                'success' => true,
                'data' => $captcha,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CAPTCHA. Please try again.',
            ], 500);
        }
    }

    /**
     * Submit a guest inquiry.
     */
    public function store(Request $request)
    {
        try {
            // Rate limiting: 3 attempts per IP per 10 minutes
            $ipAddress = $request->ip();
            $rateLimitKey = 'guest-inquiry:' . $ipAddress;
            
            if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Too many inquiry attempts. Please try again later.',
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
                'captcha_token' => 'required|string',
                'captcha_answer' => 'required|integer',
            ], [
                'first_name.required' => 'First name is required',
                'last_name.required' => 'Last name is required',
                'email.required' => 'Email address is required',
                'email.email' => 'Please provide a valid email address',
                'subject.required' => 'Please select a subject',
                'subject.in' => 'Invalid subject selected',
                'message.required' => 'Message is required',
                'message.min' => 'Message must be at least 10 characters',
                'message.max' => 'Message must not exceed 5000 characters',
                'captcha_token.required' => 'CAPTCHA token is required',
                'captcha_answer.required' => 'CAPTCHA answer is required',
                'captcha_answer.integer' => 'CAPTCHA answer must be a number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verify CAPTCHA
            $captchaValid = CaptchaService::verify(
                $request->input('captcha_token'),
                $request->input('captcha_answer')
            );
            
            if (!$captchaValid) {
                // Increment rate limiter for failed attempts
                RateLimiter::hit($rateLimitKey, 600); // 10 minutes
                
                return response()->json([
                    'success' => false,
                    'message' => 'CAPTCHA verification failed. Please try again.',
                    'errors' => [
                        'captcha_answer' => ['The CAPTCHA answer is incorrect or has expired.']
                    ],
                ], 422);
            }

            // Create the inquiry
            $inquiry = GuestInquiry::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'status' => 'pending',
            ]);

            // Increment rate limiter on success (less aggressive)
            RateLimiter::hit($rateLimitKey, 600); // 10 minutes

            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been submitted successfully! We\'ll get back to you soon.',
                'data' => [
                    'id' => $inquiry->id,
                    'reference' => 'INQ-' . str_pad($inquiry->id, 6, '0', STR_PAD_LEFT),
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Guest inquiry submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get rate limit status for current IP.
     */
    public function getRateLimitStatus(Request $request)
    {
        $ipAddress = $request->ip();
        $rateLimitKey = 'guest-inquiry:' . $ipAddress;
        
        $attempts = RateLimiter::attempts($rateLimitKey);
        $remaining = max(0, 3 - $attempts);
        
        return response()->json([
            'success' => true,
            'data' => [
                'attempts' => $attempts,
                'remaining' => $remaining,
                'limited' => RateLimiter::tooManyAttempts($rateLimitKey, 3),
                'retry_after' => RateLimiter::tooManyAttempts($rateLimitKey, 3) 
                    ? RateLimiter::availableIn($rateLimitKey) 
                    : null,
            ],
        ]);
    }
}
