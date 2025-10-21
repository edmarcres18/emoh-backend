<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Rented;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientOTPVerification;
use App\Mail\ClientEmailChangeVerification;
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

        // Generate and send OTP for email verification (real-time)
        $otp = $client->generateEmailVerificationOTP();
        Mail::to($client->email)->send(new ClientOTPVerification($client, $otp));

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
     * Login client with enhanced security
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'fingerprint' => 'nullable|string|max:100', // Browser fingerprint
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('email', $request->email)->first();

        // Check if account exists and is locked
        if ($client && $client->isLocked()) {
            $minutesRemaining = $client->getMinutesUntilUnlock();
            return response()->json([
                'success' => false,
                'message' => 'Account temporarily locked due to multiple failed login attempts.',
                'data' => [
                    'locked' => true,
                    'minutes_remaining' => $minutesRemaining,
                    'unlock_at' => $client->locked_until?->format('Y-m-d H:i:s')
                ]
            ], 423); // 423 Locked
        }

        // Validate credentials
        if (!$client || !Hash::check($request->password, $client->password)) {
            // Record failed attempt if client exists
            if ($client) {
                $client->recordFailedLogin();

                $attemptsRemaining = max(0, 5 - $client->failed_login_attempts);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'data' => [
                        'attempts_remaining' => $attemptsRemaining,
                        'warning' => $attemptsRemaining <= 2 ? 'Account will be locked after ' . $attemptsRemaining . ' more failed attempt(s)' : null
                    ]
                ], 401);
            }

            // Generic error for non-existent accounts (prevent user enumeration)
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

        // Get client IP
        $ip = $request->ip();
        $fingerprint = $request->input('fingerprint');

        // Record successful login
        $client->recordSuccessfulLogin($ip, $fingerprint);

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

        // Generate new OTP and send email (real-time)
        $otp = $client->generateEmailVerificationOTP();
        Mail::to($client->email)->send(new ClientOTPVerification($client, $otp));

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
     * Update client profile (excludes email changes - use requestEmailChange for that)
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

        $client->name = $request->name;
        $client->phone = $request->phone;

        if ($request->password) {
            $client->password = Hash::make($request->password);
        }

        $client->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'client' => $this->transformClient($client)
            ]
        ]);
    }

    /**
     * Check if client can change email (3-month restriction check)
     */
    public function checkEmailChangeEligibility(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is still active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        $canChange = $client->canChangeEmail();
        $nextChangeDate = $client->getNextEmailChangeDate();
        $daysRemaining = $client->getDaysUntilEmailChange();

        return response()->json([
            'success' => true,
            'data' => [
                'can_change_email' => $canChange,
                'last_changed_at' => $client->last_email_changed_at,
                'next_change_date' => $nextChangeDate,
                'days_remaining' => $daysRemaining,
                'restriction_period_days' => 90, // 3 months
            ]
        ]);
    }

    /**
     * Request email change - sends OTP to new email for verification
     */
    public function requestEmailChange(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is still active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        // Check 3-month restriction
        if (!$client->canChangeEmail()) {
            $daysRemaining = $client->getDaysUntilEmailChange();
            $nextChangeDate = $client->getNextEmailChangeDate();

            return response()->json([
                'success' => false,
                'message' => 'For security reasons, you can only change your email once every 3 months.',
                'data' => [
                    'can_change' => false,
                    'days_remaining' => $daysRemaining,
                    'next_change_date' => $nextChangeDate?->format('F d, Y'),
                    'last_changed_at' => $client->last_email_changed_at?->format('F d, Y')
                ]
            ], 429); // 429 Too Many Requests
        }

        $validator = Validator::make($request->all(), [
            'new_email' => 'required|string|email|max:255|unique:clients,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if new email is same as current
        if ($request->new_email === $client->email) {
            return response()->json([
                'success' => false,
                'message' => 'The new email is the same as your current email.'
            ], 400);
        }

        // Generate OTP for new email verification
        $otp = $client->generateEmailVerificationOTP();

        // Send verification email to NEW email address (real-time, no queue)
        Mail::to($request->new_email)->send(new ClientEmailChangeVerification($client, $otp, $request->new_email));

        return response()->json([
            'success' => true,
            'message' => 'A verification code has been sent to your new email address. Please check your inbox.',
            'data' => [
                'new_email' => $request->new_email,
                'expires_at' => $client->otp_expires_at
            ]
        ]);
    }

    /**
     * Verify and confirm email change using OTP
     */
    public function verifyEmailChange(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|string|email|max:255|unique:clients,email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email and 6-digit OTP code.',
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

        // Increment attempts
        $client->increment('otp_attempts');

        // Verify the OTP
        if ($client->email_verification_otp === $request->otp) {
            $oldEmail = $client->email;

            // Update email, mark as verified, and record the change timestamp
            $client->update([
                'email' => $request->new_email,
                'email_verified_at' => now(),
                'last_email_changed_at' => now(),
                'email_verification_otp' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email updated and verified successfully!',
                'data' => [
                    'client' => $this->transformClient($client->fresh()),
                    'email_changed' => true,
                    'old_email' => $oldEmail
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

    /**
     * Get authenticated client's rental properties with detailed information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientRentals(Request $request): JsonResponse
    {
        $client = $request->user();

        // Check if client account is active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support for assistance.'
            ], 403);
        }

        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:active,pending,expired,terminated,ended,all',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|string|in:start_date,end_date,monthly_rent,created_at,property_name',
            'sort_order' => 'nullable|string|in:asc,desc',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Build query with proper relationships
            $query = Rented::with(['property.category', 'property.location'])
                ->where('client_id', $client->id);

            // Apply status filter
            $status = $request->input('status', 'all');
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Apply search filter (property name or notes)
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('property', function ($propertyQuery) use ($search) {
                        $propertyQuery->where('property_name', 'like', "%{$search}%")
                            ->orWhere('details', 'like', "%{$search}%");
                    })
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('terms_conditions', 'like', "%{$search}%");
                });
            }

            // Apply date range filters
            if ($request->has('date_from') && $request->date_from) {
                $query->where('start_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->where('start_date', '<=', $request->date_to);
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            if ($sortBy === 'property_name') {
                $query->join('properties', 'rented.property_id', '=', 'properties.id')
                    ->orderBy('properties.property_name', $sortOrder)
                    ->select('rented.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $rentals = $query->paginate($perPage);

            // Update remarks for all items in the collection
            $rentals->getCollection()->each(function ($rental) {
                $rental->updateRemarks();
            });

            // Transform data to match frontend expectations with only fillable attributes
            $transformedRentals = $rentals->getCollection()->map(function ($rental) {
                // Ensure images is always an array
                $propertyImages = $rental->property->images;
                if (!is_array($propertyImages)) {
                    $propertyImages = $propertyImages ? [$propertyImages] : [];
                }
                
                return [
                    'id' => $rental->id,
                    'property' => [
                        'id' => $rental->property->id,
                        'name' => $rental->property->property_name,
                        'estimated_monthly' => $rental->property->estimated_monthly,
                        'images' => $propertyImages, // Always an array
                        'details' => $rental->property->details,
                        'status' => $rental->property->status,
                        'lot_area' => $rental->property->lot_area,
                        'floor_area' => $rental->property->floor_area,
                        'category' => $rental->property->category ? [
                            'id' => $rental->property->category->id,
                            'name' => $rental->property->category->name,
                            'description' => $rental->property->category->description,
                        ] : null,
                        'location' => $rental->property->location ? [
                            'id' => $rental->property->location->id,
                            'name' => $rental->property->location->name,
                            'address' => $rental->property->location->address,
                        ] : null,
                    ],
                    'rental_details' => [
                        'monthly_rent' => $rental->monthly_rent,
                        'formatted_monthly_rent' => $rental->formatted_monthly_rent,
                        'security_deposit' => $rental->security_deposit,
                        'formatted_security_deposit' => $rental->formatted_security_deposit,
                        'start_date' => $rental->start_date ? $rental->start_date->format('Y-m-d') : null,
                        'end_date' => $rental->end_date ? $rental->end_date->format('Y-m-d') : null,
                        'status' => $rental->status,
                        'remarks' => $rental->remarks,
                        'is_active' => $rental->isActive(),
                        'is_expired' => $rental->isExpired(),
                        'remaining_days' => $rental->remaining_days,
                        'total_duration_days' => $rental->total_duration,
                        'contract_signed_at' => $rental->contract_signed_at ? $rental->contract_signed_at->format('Y-m-d H:i:s') : null,
                    ],
                    'terms_conditions' => $rental->terms_conditions,
                    'notes' => $rental->notes,
                    'documents' => $rental->documents ?? [],
                    'created_at' => $rental->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $rental->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            // Calculate statistics for the client
            $statistics = [
                'total_rentals' => Rented::where('client_id', $client->id)->count(),
                'active_rentals' => Rented::where('client_id', $client->id)->where('status', 'active')->count(),
                'pending_rentals' => Rented::where('client_id', $client->id)->where('status', 'pending')->count(),
                'expired_rentals' => Rented::where('client_id', $client->id)->where('status', 'expired')->count(),
                'terminated_rentals' => Rented::where('client_id', $client->id)->where('status', 'terminated')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Rentals retrieved successfully',
                'data' => [
                    'rentals' => $transformedRentals,
                    'statistics' => $statistics,
                    'pagination' => [
                        'current_page' => $rentals->currentPage(),
                        'per_page' => $rentals->perPage(),
                        'total' => $rentals->total(),
                        'last_page' => $rentals->lastPage(),
                        'from' => $rentals->firstItem(),
                        'to' => $rentals->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve client rentals: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rentals. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
