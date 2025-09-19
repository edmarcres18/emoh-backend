<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\PropertyApiController;

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
        Route::get('/profile', [ClientAuthController::class, 'profile']);
        Route::put('/profile', [ClientAuthController::class, 'updateProfile']);
        Route::get('/check-active', [ClientAuthController::class, 'checkActiveStatus']);
        Route::post('/verify-email', [ClientAuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
        Route::post('/resend-otp', [ClientAuthController::class, 'resendOTP'])->middleware('throttle:10,1');
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

