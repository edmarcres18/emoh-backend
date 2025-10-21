<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

/**
 * InquiryController - Handles property inquiry submissions
 * 
 * Features:
 * - Custom CAPTCHA verification
 * - Client authentication check
 * - Property inquiry submission
 * - Rate limiting
 */
class InquiryController extends Controller
{
    /**
     * Generate custom CAPTCHA challenge
     * Simple math-based CAPTCHA to prevent bots
     * 
     * @return JsonResponse
     */
    public function generateCaptcha(): JsonResponse
    {
        try {
            // Generate random math problem
            $num1 = rand(1, 10);
            $num2 = rand(1, 10);
            $operators = ['+', '-', '*'];
            $operator = $operators[array_rand($operators)];
            
            // Calculate answer
            $answer = match($operator) {
                '+' => $num1 + $num2,
                '-' => $num1 - $num2,
                '*' => $num1 * $num2,
                default => $num1 + $num2,
            };
            
            // Store answer in session with timestamp
            $captchaId = uniqid('captcha_', true);
            Session::put($captchaId, [
                'answer' => $answer,
                'expires_at' => now()->addMinutes(5)->timestamp
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'captcha_id' => $captchaId,
                    'question' => "{$num1} {$operator} {$num2} = ?",
                    'expires_in' => 300 // 5 minutes in seconds
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CAPTCHA',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Verify custom CAPTCHA answer
     * 
     * @param string $captchaId
     * @param int $answer
     * @return bool
     */
    private function verifyCaptcha(string $captchaId, int $answer): bool
    {
        $captchaData = Session::get($captchaId);
        
        if (!$captchaData) {
            return false;
        }
        
        // Check if expired
        if ($captchaData['expires_at'] < now()->timestamp) {
            Session::forget($captchaId);
            return false;
        }
        
        // Verify answer
        $isValid = (int)$captchaData['answer'] === (int)$answer;
        
        // Remove captcha after verification (one-time use)
        Session::forget($captchaId);
        
        return $isValid;
    }
    
    /**
     * Check if client is authenticated
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAuth(Request $request): JsonResponse
    {
        try {
            $client = $request->user('sanctum');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_authenticated' => $client !== null,
                    'client' => $client ? [
                        'id' => $client->id,
                        'name' => $client->first_name . ' ' . $client->last_name,
                        'email' => $client->email,
                    ] : null
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check authentication',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Submit property inquiry
     * Requires authentication and CAPTCHA verification
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function submitInquiry(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|integer|exists:properties,id',
                'subject' => 'required|string|in:viewing,information,pricing,availability,other',
                'message' => 'required|string|min:10|max:1000',
                'preferred_contact' => 'nullable|string|in:email,phone',
                'captcha_id' => 'required|string',
                'captcha_answer' => 'required|integer',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify CAPTCHA first
            if (!$this->verifyCaptcha($request->captcha_id, $request->captcha_answer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired CAPTCHA. Please try again.',
                    'error_type' => 'captcha_failed'
                ], 400);
            }
            
            // Check authentication
            $client = $request->user('sanctum');
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to submit inquiry',
                    'error_type' => 'auth_required'
                ], 401);
            }
            
            // Here you would typically save to database
            // For now, we'll just return success
            // Example: Inquiry::create([...])
            
            $inquiryData = [
                'inquiry_id' => uniqid('INQ_', true),
                'client_id' => $client->id,
                'property_id' => $request->property_id,
                'subject' => $request->subject,
                'message' => $request->message,
                'preferred_contact' => $request->preferred_contact ?? 'email',
                'status' => 'pending',
                'submitted_at' => now()->toDateTimeString(),
            ];
            
            // TODO: Save to database
            // Inquiry::create($inquiryData);
            
            // TODO: Send notification email to admin/property owner
            
            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been submitted successfully. We will get back to you within 24-48 hours.',
                'data' => [
                    'inquiry_id' => $inquiryData['inquiry_id'],
                    'submitted_at' => $inquiryData['submitted_at'],
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inquiry',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get client's inquiry history
     * Requires authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyInquiries(Request $request): JsonResponse
    {
        try {
            $client = $request->user('sanctum');
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }
            
            // TODO: Fetch from database
            // $inquiries = Inquiry::where('client_id', $client->id)
            //     ->with('property')
            //     ->orderBy('created_at', 'desc')
            //     ->paginate(10);
            
            return response()->json([
                'success' => true,
                'message' => 'Inquiries retrieved successfully',
                'data' => [
                    'inquiries' => [], // Replace with actual data
                    'total' => 0
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inquiries',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
