<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class GuestInquiryController extends Controller
{
    /**
     * Store a new guest inquiry with reCAPTCHA verification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Rate limiting by IP address (3 attempts per 15 minutes)
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
                'recaptcha_token' => 'required|string',
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
                'recaptcha_token.required' => 'reCAPTCHA verification is required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verify reCAPTCHA token
            $recaptchaVerified = $this->verifyRecaptcha($request->input('recaptcha_token'), $ipAddress);
            
            if (!$recaptchaVerified['success']) {
                // Increment rate limiter for failed attempts
                RateLimiter::hit($rateLimitKey, 900); // 15 minutes
                
                return response()->json([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed. Please try again.',
                    'errors' => ['recaptcha' => [$recaptchaVerified['message']]],
                ], 422);
            }

            // Check reCAPTCHA score (v3 provides a score from 0.0 to 1.0)
            if (isset($recaptchaVerified['score']) && $recaptchaVerified['score'] < 0.5) {
                RateLimiter::hit($rateLimitKey, 900);
                
                Log::warning('Low reCAPTCHA score', [
                    'score' => $recaptchaVerified['score'],
                    'ip' => $ipAddress,
                    'email' => $request->input('email'),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Suspicious activity detected. Please try again.',
                    'errors' => ['recaptcha' => ['Verification score too low']],
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

            // Increment rate limiter for successful submission
            RateLimiter::hit($rateLimitKey, 900);

            // Log successful inquiry
            Log::info('Guest inquiry created', [
                'inquiry_id' => $inquiry->id,
                'email' => $inquiry->email,
                'subject' => $inquiry->subject,
                'ip' => $ipAddress,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been submitted successfully. We\'ll get back to you within 24-48 hours.',
                'data' => [
                    'inquiry_id' => $inquiry->id,
                    'reference_number' => 'INQ-' . str_pad($inquiry->id, 6, '0', STR_PAD_LEFT),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Guest inquiry submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your inquiry. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Verify reCAPTCHA token with Google
     *
     * @param string $token
     * @param string $ipAddress
     * @return array
     */
    private function verifyRecaptcha(string $token, string $ipAddress): array
    {
        $secretKey = env('RECAPTCHA_SECRET_KEY');

        if (empty($secretKey)) {
            Log::error('reCAPTCHA secret key not configured');
            return [
                'success' => false,
                'message' => 'reCAPTCHA is not properly configured',
            ];
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ipAddress,
            ]);

            $result = $response->json();

            if (!$response->successful() || !isset($result['success'])) {
                Log::error('reCAPTCHA verification request failed', [
                    'status' => $response->status(),
                    'response' => $result,
                ]);

                return [
                    'success' => false,
                    'message' => 'Unable to verify reCAPTCHA',
                ];
            }

            if ($result['success']) {
                return [
                    'success' => true,
                    'score' => $result['score'] ?? null,
                    'action' => $result['action'] ?? null,
                ];
            }

            // Handle specific error codes
            $errorCodes = $result['error-codes'] ?? [];
            $errorMessage = 'reCAPTCHA verification failed';

            if (in_array('timeout-or-duplicate', $errorCodes)) {
                $errorMessage = 'reCAPTCHA token expired or already used';
            } elseif (in_array('invalid-input-response', $errorCodes)) {
                $errorMessage = 'Invalid reCAPTCHA token';
            }

            Log::warning('reCAPTCHA verification failed', [
                'error_codes' => $errorCodes,
                'ip' => $ipAddress,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_codes' => $errorCodes,
            ];

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to verify reCAPTCHA',
            ];
        }
    }
}
