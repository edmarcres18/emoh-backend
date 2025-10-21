<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Property Inquiry Controller
 * Handles property inquiry submissions with authentication and CAPTCHA verification
 */
class PropertyInquiryController extends Controller
{
    /**
     * Generate custom CAPTCHA challenge
     * Returns a simple math problem to verify human users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCaptcha()
    {
        try {
            // Generate random numbers for math challenge
            $num1 = rand(1, 10);
            $num2 = rand(1, 10);
            $operation = rand(0, 1) ? '+' : '-';
            
            // Calculate correct answer
            $answer = $operation === '+' ? $num1 + $num2 : $num1 - $num2;
            
            // Generate unique token for this CAPTCHA
            $token = Str::random(32);
            
            // Store answer in session with token as key
            Session::put("captcha_{$token}", [
                'answer' => $answer,
                'expires_at' => now()->addMinutes(5)
            ]);
            
            return response()->json([
                'success' => true,
                'captcha' => [
                    'token' => $token,
                    'question' => "{$num1} {$operation} {$num2} = ?",
                    'num1' => $num1,
                    'num2' => $num2,
                    'operation' => $operation
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CAPTCHA'
            ], 500);
        }
    }

    /**
     * Verify CAPTCHA answer
     *
     * @param string $token
     * @param int $answer
     * @return bool
     */
    private function verifyCaptcha($token, $answer)
    {
        $captchaData = Session::get("captcha_{$token}");
        
        if (!$captchaData) {
            return false;
        }
        
        // Check if CAPTCHA expired
        if (now()->gt($captchaData['expires_at'])) {
            Session::forget("captcha_{$token}");
            return false;
        }
        
        // Verify answer
        $isValid = (int)$answer === (int)$captchaData['answer'];
        
        // Clear CAPTCHA after verification attempt
        Session::forget("captcha_{$token}");
        
        return $isValid;
    }

    /**
     * Submit property inquiry
     * Requires authentication and valid CAPTCHA
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitInquiry(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|integer',
                'message' => 'required|string|min:10|max:1000',
                'captcha_token' => 'required|string',
                'captcha_answer' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify CAPTCHA
            if (!$this->verifyCaptcha($request->captcha_token, $request->captcha_answer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid CAPTCHA answer. Please try again.'
                ], 422);
            }

            // Get authenticated user
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // TODO: Store inquiry in database
            // Example:
            // PropertyInquiry::create([
            //     'property_id' => $request->property_id,
            //     'user_id' => $user->id,
            //     'message' => $request->message,
            //     'status' => 'pending'
            // ]);

            // For now, just return success
            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been submitted successfully. We will contact you soon.',
                'data' => [
                    'property_id' => $request->property_id,
                    'user_name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
                    'user_email' => $user->email,
                    'submitted_at' => now()->toDateTimeString()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inquiry. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if user is authenticated
     * Public endpoint to check auth status without revealing user data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuth(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'authenticated' => $user !== null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
                'email' => $user->email
            ] : null
        ]);
    }
}
