<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\HealthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health check endpoints
Route::get('/health', [HealthController::class, 'health']);
Route::get('/health/detailed', [HealthController::class, 'detailed']);

Route::get('/contact-info', [SiteSettingsController::class, 'getContactInfo']);

// Client Authentication Routes
Route::prefix('client')->group(function () {
    // Public routes with enhanced rate limiting
    Route::post('/register', [ClientAuthController::class, 'register'])->middleware('rate.limit:register,5,1');
    Route::post('/login', [ClientAuthController::class, 'login'])->middleware('rate.limit:login,10,1');
    Route::get('/auth/google', [ClientAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [ClientAuthController::class, 'handleGoogleCallback']);

    // Protected routes
    Route::middleware('auth:client')->group(function () {
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        Route::get('/me', [ClientAuthController::class, 'profile']); // Alias for /profile (REST convention)
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::put('/profile', [ClientAuthController::class, 'updateProfile']);
        Route::get('/check-active', [ClientAuthController::class, 'checkActiveStatus']);
        Route::post('/verify-email', [ClientAuthController::class, 'verifyEmail'])->middleware('rate.limit:verify-email,10,1');
        Route::post('/resend-otp', [ClientAuthController::class, 'resendOTP'])->middleware('rate.limit:resend-otp,5,1');

        // Email change routes with OTP verification
        Route::get('/check-email-change-eligibility', [ClientAuthController::class, 'checkEmailChangeEligibility']);
        Route::post('/request-email-change', [ClientAuthController::class, 'requestEmailChange'])->middleware('rate.limit:email-change,3,1');
        Route::post('/verify-email-change', [ClientAuthController::class, 'verifyEmailChange'])->middleware('rate.limit:verify-email-change,10,1');

        // Client rental properties
        Route::get('/my-rentals', [PropertyApiController::class, 'getClientRentedProperties']);
    });
});

// Property API Routes with rate limiting
Route::prefix('properties')->middleware(['api', 'rate.limit:properties,100,1'])->group(function () {
    // Public routes - no authentication required for property browsing
    Route::get('/by-status-properties', [PropertyApiController::class, 'getPropertiesByStatus']);
    Route::get('/featured-properties', [PropertyApiController::class, 'getFeaturedProperties']);
    Route::get('/stats-properties', [PropertyApiController::class, 'getPropertyStats']);
    Route::get('/statuses-properties', [PropertyApiController::class, 'getAvailableStatuses']);
});

