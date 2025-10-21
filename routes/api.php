<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\SiteSettingApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/contact-info', [SiteSettingsController::class, 'getContactInfo']);

// Client Authentication Routes
Route::prefix('client')->group(function () {
    // Public routes
    Route::post('/register', [ClientAuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [ClientAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/auth/google', [ClientAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [ClientAuthController::class, 'handleGoogleCallback']);

    // Protected routes
    Route::middleware('auth:client')->group(function () {
        Route::post('/logout', [ClientAuthController::class, 'logout']);
        Route::get('/me', [ClientAuthController::class, 'profile']); // Alias for /profile (REST convention)
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::put('/profile', [ClientAuthController::class, 'updateProfile']);
        Route::get('/check-active', [ClientAuthController::class, 'checkActiveStatus']);
        Route::post('/verify-email', [ClientAuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
        Route::post('/resend-otp', [ClientAuthController::class, 'resendOTP'])->middleware('throttle:10,1');

        // Email change routes with OTP verification
        Route::get('/check-email-change-eligibility', [ClientAuthController::class, 'checkEmailChangeEligibility']);
        Route::post('/request-email-change', [ClientAuthController::class, 'requestEmailChange'])->middleware('throttle:5,1');
        Route::post('/verify-email-change', [ClientAuthController::class, 'verifyEmailChange'])->middleware('throttle:10,1');

        // Client rental properties routes
        Route::get('/my-rentals', [ClientAuthController::class, 'getClientRentals']);
    });
});

// Property API Routes
Route::prefix('properties')->middleware(['api'])->group(function () {
    // Public routes - no authentication required for property browsing
    Route::get('/by-status-properties', [PropertyApiController::class, 'getPropertiesByStatus']);
    Route::get('/featured-properties', [PropertyApiController::class, 'getFeaturedProperties']);
    Route::get('/stats-properties', [PropertyApiController::class, 'getPropertyStats']);
    Route::get('/statuses-properties', [PropertyApiController::class, 'getAvailableStatuses']);
});

// Site Settings API Routes
Route::prefix('site-settings')->group(function () {
    // Get all settings
    Route::get('/all', [SiteSettingApiController::class, 'getAllSettings']);
    
    // Site name
    Route::get('/site-name', [SiteSettingApiController::class, 'getSiteName']);
    Route::put('/site-name', [SiteSettingApiController::class, 'updateSiteName']);
    
    // Site logo
    Route::get('/site-logo', [SiteSettingApiController::class, 'getSiteLogo']);
    Route::put('/site-logo', [SiteSettingApiController::class, 'updateSiteLogo']);
    
    // Site favicon
    Route::get('/site-favicon', [SiteSettingApiController::class, 'getSiteFavicon']);
    Route::put('/site-favicon', [SiteSettingApiController::class, 'updateSiteFavicon']);
    
    // Site description
    Route::get('/site-description', [SiteSettingApiController::class, 'getSiteDescription']);
    Route::put('/site-description', [SiteSettingApiController::class, 'updateSiteDescription']);
    
    // Maintenance mode
    Route::get('/maintenance-mode', [SiteSettingApiController::class, 'getMaintenanceMode']);
    Route::put('/maintenance-mode', [SiteSettingApiController::class, 'updateMaintenanceMode']);
    
    // Contact email
    Route::get('/contact-email', [SiteSettingApiController::class, 'getContactEmail']);
    Route::put('/contact-email', [SiteSettingApiController::class, 'updateContactEmail']);
    
    // Contact phone
    Route::get('/contact-phone', [SiteSettingApiController::class, 'getContactPhone']);
    Route::put('/contact-phone', [SiteSettingApiController::class, 'updateContactPhone']);
    
    // Phone number
    Route::get('/phone-number', [SiteSettingApiController::class, 'getPhoneNumber']);
    Route::put('/phone-number', [SiteSettingApiController::class, 'updatePhoneNumber']);
    
    // Address
    Route::get('/address', [SiteSettingApiController::class, 'getAddress']);
    Route::put('/address', [SiteSettingApiController::class, 'updateAddress']);
    
    // Social media - Facebook
    Route::get('/social-facebook', [SiteSettingApiController::class, 'getSocialFacebook']);
    Route::put('/social-facebook', [SiteSettingApiController::class, 'updateSocialFacebook']);
    
    // Social media - Twitter
    Route::get('/social-twitter', [SiteSettingApiController::class, 'getSocialTwitter']);
    Route::put('/social-twitter', [SiteSettingApiController::class, 'updateSocialTwitter']);
    
    // Social media - Instagram
    Route::get('/social-instagram', [SiteSettingApiController::class, 'getSocialInstagram']);
    Route::put('/social-instagram', [SiteSettingApiController::class, 'updateSocialInstagram']);
    
    // Social media - LinkedIn
    Route::get('/social-linkedin', [SiteSettingApiController::class, 'getSocialLinkedin']);
    Route::put('/social-linkedin', [SiteSettingApiController::class, 'updateSocialLinkedin']);
    
    // Social media - Telegram
    Route::get('/social-telegram', [SiteSettingApiController::class, 'getSocialTelegram']);
    Route::put('/social-telegram', [SiteSettingApiController::class, 'updateSocialTelegram']);
    
    // Social media - Viber
    Route::get('/social-viber', [SiteSettingApiController::class, 'getSocialViber']);
    Route::put('/social-viber', [SiteSettingApiController::class, 'updateSocialViber']);
    
    // Social media - WhatsApp
    Route::get('/social-whatsapp', [SiteSettingApiController::class, 'getSocialWhatsapp']);
    Route::put('/social-whatsapp', [SiteSettingApiController::class, 'updateSocialWhatsapp']);
    
    // Google Analytics
    Route::get('/google-analytics-id', [SiteSettingApiController::class, 'getGoogleAnalyticsId']);
    Route::put('/google-analytics-id', [SiteSettingApiController::class, 'updateGoogleAnalyticsId']);
});
