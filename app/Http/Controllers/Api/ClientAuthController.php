<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientOTPVerification;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    /**
     * Normalize client payload for API responses to avoid leaking sensitive attributes
     */
    private function transformClient(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'avatar' => $client->avatar,
            'google_id' => $client->google_id,
            'email_verified_at' => $client->email_verified_at,
            'is_active' => (bool) $client->is_active,
            'created_at' => $client->created_at,
            'updated_at' => $client->updated_at,
        ];
    }

    /**
     * Resolve a descriptive token name for the user's device
     */
    private function resolveTokenName(Request $request): string
    {
        $deviceHeader = (string) $request->header('X-Device-Name', 'client');
        return Str::of($deviceHeader)->limit(60, '')->trim()->whenEmpty(fn () => 'client')->toString();
    }

    /**
     * Register a new client
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_active' => true, // New clients are active by default
        ]);

        // Generate and queue OTP for email verification
        $otp = $client->generateEmailVerificationOTP();
        Mail::to($client->email)->queue(new ClientOTPVerification($client, $otp));

        $token = $client->createToken($this->resolveTokenName($request))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Client registered successfully. Please check your email for the verification code.',
            'data' => [
                'client' => $this->transformClient($client),
                'token' => $token,
                'requires_email_verification' => true
            ]
        ], 201);
    }

    /**
     * Login client
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if client account is active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        $token = $client->createToken($this->resolveTokenName($request))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'client' => $this->transformClient($client),
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout client
     */
    public function logout(Request $request): JsonResponse
    {
        $client = $request->user();

        // Always allow logout, even for inactive accounts
        $token = $client?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Check if authenticated client is active (middleware helper)
     */
    public function checkActiveStatus(Request $request): JsonResponse
    {
        $client = $request->user();

        if (!$client || !$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.',
                'is_active' => false
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account is active',
            'is_active' => true,
            'data' => [
                'client' => $this->transformClient($client)
            ]
        ]);
    }

    /**
     * Verify client email using OTP
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid 6-digit OTP code.',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = $request->user();

        // Check if client account is active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        // Check if email is already verified
        if ($client->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Your email is already verified.'
            ], 400);
        }

        // Check if OTP has expired
        if ($client->isOTPExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code has expired. Please request a new one.',
                'expired' => true
            ], 400);
        }

        // Check if too many attempts
        if ($client->hasExceededOTPAttempts()) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new verification code.',
                'exceeded_attempts' => true
            ], 400);
        }

        // Verify the OTP
        if ($client->verifyOTP($request->otp)) {
            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully!',
                'data' => [
                    'client' => $this->transformClient($client->fresh()),
                    'email_verified' => true
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code. Please try again.',
            'attempts_remaining' => max(0, 5 - $client->otp_attempts)
        ], 400);
    }

    /**
     * Resend OTP for email verification
     */
    public function resendOTP(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        // Check if email is already verified
        if ($client->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Your email is already verified.'
            ], 400);
        }

        // Generate new OTP and queue email
        $otp = $client->generateEmailVerificationOTP();
        Mail::to($client->email)->queue(new ClientOTPVerification($client, $otp));

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
            'data' => [
                'expires_at' => $client->otp_expires_at
            ]
        ]);
    }

    /**
     * Get authenticated client profile
     */
    public function profile(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is still active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'client' => $this->transformClient($client)
            ]
        ]);
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is still active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $emailChanged = false;
        $oldEmail = $client->email;

        // Check if email is being changed
        if ($request->email !== $client->email) {
            $emailChanged = true;
            
            // Reset email verification since email changed
            $client->email_verified_at = null;
            
            // Generate new OTP for email verification
            $otp = $client->generateEmailVerificationOTP();
        }

        $client->name = $request->name;
        $client->email = $request->email;
        $client->phone = $request->phone;

        if ($request->password) {
            $client->password = Hash::make($request->password);
        }

        $client->save();

        // Send verification email if email was changed
        if ($emailChanged) {
            Mail::to($client->email)->queue(new ClientOTPVerification($client, $otp));
        }

        $message = $emailChanged 
            ? 'Profile updated successfully! Please check your new email for a verification code.'
            : 'Profile updated successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'client' => $this->transformClient($client),
                'email_changed' => $emailChanged
            ]
        ]);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(): JsonResponse
    {
        try {
            // Stateless for APIs behind load balancers / SPA
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if client already exists with this Google ID
            $client = Client::where('google_id', $googleUser->id)->first();

            if ($client) {
                // Check if client account is active
                if (!$client->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been deactivated. Please contact support for assistance.'
                    ], 403);
                }

                // Client exists and is active, log them in
                $token = $client->createToken('google')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'client' => $this->transformClient($client),
                        'token' => $token
                    ]
                ]);
            }

            // Check if client exists with this email
            $existingClient = Client::where('email', $googleUser->email)->first();

            if ($existingClient) {
                // Check if existing client account is active
                if (!$existingClient->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been deactivated. Please contact support for assistance.'
                    ], 403);
                }

                // Update existing client with Google ID
                $existingClient->google_id = $googleUser->id;
                $existingClient->avatar = $googleUser->avatar;
                $existingClient->save();

                $token = $existingClient->createToken('google')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Account linked successfully',
                    'data' => [
                        'client' => $this->transformClient($existingClient),
                        'token' => $token
                    ]
                ]);
            }

            // Create new client (Google OAuth users are auto-verified)
            $newClient = Client::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'email_verified_at' => now(),
                'is_active' => true, // New Google clients are active by default
            ]);

            $token = $newClient->createToken('google')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'client' => $this->transformClient($newClient),
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
