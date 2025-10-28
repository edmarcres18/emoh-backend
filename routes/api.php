<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\SiteSettingApiController;
use App\Http\Controllers\Api\ClientInquiryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/contact-info', [SiteSettingsController::class, 'getContactInfo']);

// Categories and Locations API Routes - Public access for property search
    Route::get('/categories', function () {
        $categories = \App\Models\Category::select('id', 'name', 'description')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    });

    Route::get('/locations', function () {
        try {
            $locations = \App\Models\Locations::select('id', 'name', 'description')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Locations retrieved successfully',
                'data' => $locations
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching locations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve locations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'data' => []
            ], 200);
        }
    });
// (Removed unnecessary group wrapper to avoid Router::group signature error)

// Client Authentication Routes
Route::prefix('client')->group(function () {
    // Public routes - Generous throttle limits since controller has its own rate limiting
    Route::post('/register', [ClientAuthController::class, 'register']);
    Route::post('/login', [ClientAuthController::class, 'login']);
    Route::get('/auth/google', [ClientAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [ClientAuthController::class, 'handleGoogleCallback']);

    // Protected routes
    Route::middleware('auth:client')->group(function () {
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        Route::get('/me', [ClientAuthController::class, 'profile']); // Alias for /profile (REST convention)
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::put('/profile', [ClientAuthController::class, 'updateProfile']);
        Route::get('/check-active', [ClientAuthController::class, 'checkActiveStatus']);
        Route::post('/verify-email', [ClientAuthController::class, 'verifyEmail']);
        Route::post('/resend-otp', [ClientAuthController::class, 'resendOTP']);

        // Email change routes with OTP verification
        Route::get('/check-email-change-eligibility', [ClientAuthController::class, 'checkEmailChangeEligibility']);
        Route::post('/request-email-change', [ClientAuthController::class, 'requestEmailChange']);
        Route::post('/verify-email-change', [ClientAuthController::class, 'verifyEmailChange']);

        // Client rental properties routes
        Route::get('/my-rentals', [ClientAuthController::class, 'getClientRentals']);

        // Client inquiries routes
        Route::post('/inquiries', [ClientInquiryController::class, 'store']);
        Route::get('/inquiries', [ClientInquiryController::class, 'index']);
    });
});

// Property API Routes
Route::prefix('properties')->middleware(['api'])->group(function () {
    // Public routes - no authentication required for property browsing
    Route::get('/all-properties', [PropertyApiController::class, 'getAllProperties']);
    Route::get('/by-status-properties', [PropertyApiController::class, 'getPropertiesByStatus']);
    Route::get('/featured-properties', [PropertyApiController::class, 'getFeaturedProperties']);
    Route::get('/stats-properties', [PropertyApiController::class, 'getPropertyStats']);
    Route::get('/statuses-properties', [PropertyApiController::class, 'getAvailableStatuses']);
});

// Site Settings API Routes - Public read access with rate limiting, protected write access
Route::prefix('site-settings')->group(function () {
    // Public GET routes
        // Get all settings
        Route::get('/all', [SiteSettingApiController::class, 'getAllSettings']);

        // Site name
        Route::get('/site-name', [SiteSettingApiController::class, 'getSiteName']);

        // Site logo
        Route::get('/site-logo', [SiteSettingApiController::class, 'getSiteLogo']);

        // Site favicon
        Route::get('/site-favicon', [SiteSettingApiController::class, 'getSiteFavicon']);

        // Site description
        Route::get('/site-description', [SiteSettingApiController::class, 'getSiteDescription']);

        // Maintenance mode
        Route::get('/maintenance-mode', [SiteSettingApiController::class, 'getMaintenanceMode']);

        // Contact email
        Route::get('/contact-email', [SiteSettingApiController::class, 'getContactEmail']);

        // Contact phone
        Route::get('/contact-phone', [SiteSettingApiController::class, 'getContactPhone']);

        // Phone number
        Route::get('/phone-number', [SiteSettingApiController::class, 'getPhoneNumber']);

        // Address
        Route::get('/address', [SiteSettingApiController::class, 'getAddress']);

        // Social media - Facebook
        Route::get('/social-facebook', [SiteSettingApiController::class, 'getSocialFacebook']);

        // Social media - Twitter
        Route::get('/social-twitter', [SiteSettingApiController::class, 'getSocialTwitter']);

        // Social media - Instagram
        Route::get('/social-instagram', [SiteSettingApiController::class, 'getSocialInstagram']);

        // Social media - LinkedIn
        Route::get('/social-linkedin', [SiteSettingApiController::class, 'getSocialLinkedin']);

        // Social media - Telegram
        Route::get('/social-telegram', [SiteSettingApiController::class, 'getSocialTelegram']);

        // Social media - Viber
        Route::get('/social-viber', [SiteSettingApiController::class, 'getSocialViber']);

        // Social media - WhatsApp
        Route::get('/social-whatsapp', [SiteSettingApiController::class, 'getSocialWhatsapp']);

        // Google Analytics
        Route::get('/google-analytics-id', [SiteSettingApiController::class, 'getGoogleAnalyticsId']);
    // (Removed unnecessary group wrapper to avoid Router::group signature error)

    // Protected PUT routes - Require authentication (admin only)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::put('/site-name', [SiteSettingApiController::class, 'updateSiteName']);
        Route::put('/site-logo', [SiteSettingApiController::class, 'updateSiteLogo']);
        Route::put('/site-favicon', [SiteSettingApiController::class, 'updateSiteFavicon']);
        Route::put('/site-description', [SiteSettingApiController::class, 'updateSiteDescription']);
        Route::put('/maintenance-mode', [SiteSettingApiController::class, 'updateMaintenanceMode']);
        Route::put('/contact-email', [SiteSettingApiController::class, 'updateContactEmail']);
        Route::put('/contact-phone', [SiteSettingApiController::class, 'updateContactPhone']);
        Route::put('/phone-number', [SiteSettingApiController::class, 'updatePhoneNumber']);
        Route::put('/address', [SiteSettingApiController::class, 'updateAddress']);
        Route::put('/social-facebook', [SiteSettingApiController::class, 'updateSocialFacebook']);
        Route::put('/social-twitter', [SiteSettingApiController::class, 'updateSocialTwitter']);
        Route::put('/social-instagram', [SiteSettingApiController::class, 'updateSocialInstagram']);
        Route::put('/social-linkedin', [SiteSettingApiController::class, 'updateSocialLinkedin']);
        Route::put('/social-telegram', [SiteSettingApiController::class, 'updateSocialTelegram']);
        Route::put('/social-viber', [SiteSettingApiController::class, 'updateSocialViber']);
        Route::put('/social-whatsapp', [SiteSettingApiController::class, 'updateSocialWhatsapp']);
        Route::put('/google-analytics-id', [SiteSettingApiController::class, 'updateGoogleAnalyticsId']);
    });
});
