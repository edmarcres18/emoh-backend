<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\RentedController;
use App\Http\Controllers\DatabaseBackupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard API routes
    Route::post('dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::get('dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    Route::resource('categories', CategoryController::class);
    Route::resource('locations', LocationsController::class);

    // Property routes
    Route::resource('properties', PropertyController::class);

    // Additional property routes
    Route::patch('properties/{property}/toggle-featured', [PropertyController::class, 'toggleFeatured'])
        ->name('properties.toggle-featured');
    Route::get('api/properties/stats', [PropertyController::class, 'stats'])
        ->name('properties.stats');
    Route::get('api/properties/featured', [PropertyController::class, 'featured'])
        ->name('properties.featured');
    Route::patch('api/properties/bulk-status', [PropertyController::class, 'bulkUpdateStatus'])
        ->name('properties.bulk-status');
    Route::delete('api/properties/bulk-delete', [PropertyController::class, 'bulkDelete'])
        ->name('properties.bulk-delete');
    Route::get('api/properties/export', [PropertyController::class, 'export'])
        ->name('properties.export');

    // Admin routes - protected by role middleware
    Route::prefix('admin')->middleware(['role:System Admin|Admin'])->group(function () {
        // Roles page and API routes
        Route::get('roles', function () {
            return Inertia::render('Admin/Roles/Index');
        })->name('admin.roles.index');

        // Permissions page and API routes
        Route::get('permissions', function () {
            return Inertia::render('Admin/Permissions/Index');
        })->name('admin.permissions.index');

        // Users page and resource routes
        Route::get('users', [UserController::class, 'indexPage'])->name('admin.users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        // Clients page and resource routes
        Route::get('clients', [ClientController::class, 'indexPage'])->name('admin.clients.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('admin.clients.show');
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('admin.clients.update');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');
        Route::post('clients/{client}/verify-email', [ClientController::class, 'verifyEmail'])->name('admin.clients.verify-email');
        Route::post('clients/{client}/unverify-email', [ClientController::class, 'unverifyEmail'])->name('admin.clients.unverify-email');
        Route::post('clients/{client}/revoke-tokens', [ClientController::class, 'revokeTokens'])->name('admin.clients.revoke-tokens');
        Route::patch('clients/{client}/reset-password', [ClientController::class, 'resetPassword'])->name('admin.clients.reset-password');
        Route::patch('clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])->name('admin.clients.toggle-active');
        Route::post('clients/{client}/activate', [ClientController::class, 'activate'])->name('admin.clients.activate');
        Route::post('clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('admin.clients.deactivate');

        // Site Settings page and routes
        Route::get('site-settings', [SiteSettingsController::class, 'index'])->name('admin.site-settings.index');
        Route::put('site-settings', [SiteSettingsController::class, 'update'])->name('admin.site-settings.update');
        Route::delete('site-settings/logo', [SiteSettingsController::class, 'removeLogo'])->name('admin.site-settings.remove-logo');
        Route::delete('site-settings/favicon', [SiteSettingsController::class, 'removeFavicon'])->name('admin.site-settings.remove-favicon');
        Route::post('site-settings/clear-cache', [SiteSettingsController::class, 'clearCache'])->name('admin.site-settings.clear-cache');

        // Rented page and resource routes
        Route::get('rented', [RentedController::class, 'indexPage'])->name('admin.rented.index');
        Route::get('rented/create', [RentedController::class, 'create'])->name('admin.rented.create');
        Route::post('rented', [RentedController::class, 'store'])->name('admin.rented.store');
        Route::get('rented/{rented}', [RentedController::class, 'showPage'])->name('admin.rented.show');
        Route::get('rented/{rented}/edit', [RentedController::class, 'edit'])->name('admin.rented.edit');
        Route::put('rented/{rented}', [RentedController::class, 'update'])->name('admin.rented.update');
        Route::delete('rented/{rented}', [RentedController::class, 'destroy'])->name('admin.rented.destroy');
        Route::post('rented/{rented}/activate', [RentedController::class, 'activate'])->name('admin.rented.activate');
        Route::post('rented/{rented}/terminate', [RentedController::class, 'terminate'])->name('admin.rented.terminate');
        Route::post('rented/{rented}/mark-expired', [RentedController::class, 'markExpired'])->name('admin.rented.mark-expired');
        Route::post('rented/{rented}/renew', [RentedController::class, 'renew'])->name('admin.rented.renew');
        Route::post('rented/{rented}/end', [RentedController::class, 'end'])->name('admin.rented.end');

        // Database Backup page and resource routes
        Route::get('database-backup', [DatabaseBackupController::class, 'index'])->name('database-backup.index');
        Route::post('database-backup', [DatabaseBackupController::class, 'store'])->name('database-backup.store');
        Route::delete('database-backup/{backup}', [DatabaseBackupController::class, 'destroy'])->name('database-backup.destroy');
        Route::get('database-backup/{backup}/download', [DatabaseBackupController::class, 'download'])->name('database-backup.download');

        // Trash management routes
        Route::post('database-backup/{backup}/restore', [DatabaseBackupController::class, 'restore'])->name('database-backup.restore');
        Route::delete('database-backup/{backup}/force-delete', [DatabaseBackupController::class, 'forceDelete'])->name('database-backup.force-delete');

        // API routes for roles management
        Route::prefix('api')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('admin.api.roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('admin.api.roles.store');
            Route::get('roles/{role}', [RoleController::class, 'show'])->name('admin.api.roles.show');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('admin.api.roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('admin.api.roles.destroy');
            Route::get('roles-permissions', [RoleController::class, 'permissions'])->name('admin.api.roles.permissions');

            // API routes for permissions management
            Route::get('permissions', [PermissionController::class, 'index'])->name('admin.api.permissions.index');
            Route::post('permissions', [PermissionController::class, 'store'])->name('admin.api.permissions.store');
            Route::get('permissions/{permission}', [PermissionController::class, 'show'])->name('admin.api.permissions.show');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('admin.api.permissions.update');
            Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('admin.api.permissions.destroy');

            // API routes for users management
            Route::get('users', [UserController::class, 'index'])->name('admin.api.users.index');
            Route::post('users', [UserController::class, 'store'])->name('admin.api.users.store');
            Route::get('users/{user}', [UserController::class, 'show'])->name('admin.api.users.show');
            Route::put('users/{user}', [UserController::class, 'update'])->name('admin.api.users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('admin.api.users.destroy');
            Route::get('users-roles', [UserController::class, 'roles'])->name('admin.api.users.roles');
            Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.api.users.reset-password');

            // API routes for clients management
            Route::get('clients', [ClientController::class, 'index'])->name('admin.api.clients.index');
            Route::get('clients/{client}', [ClientController::class, 'show'])->name('admin.api.clients.show');
            Route::put('clients/{client}', [ClientController::class, 'update'])->name('admin.api.clients.update');
            Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('admin.api.clients.destroy');

            // API routes for rented management
            Route::get('rented', [RentedController::class, 'index'])->name('admin.api.rented.index');
            Route::post('rented', [RentedController::class, 'store'])->name('admin.api.rented.store');
            Route::get('rented/{rented}', [RentedController::class, 'show'])->name('admin.api.rented.show');
            Route::put('rented/{rented}', [RentedController::class, 'update'])->name('admin.api.rented.update');
            Route::delete('rented/{rented}', [RentedController::class, 'destroy'])->name('admin.api.rented.destroy');
            Route::post('rented/{rented}/activate', [RentedController::class, 'activate'])->name('admin.api.rented.activate');
            Route::post('rented/{rented}/terminate', [RentedController::class, 'terminate'])->name('admin.api.rented.terminate');
            Route::post('rented/{rented}/mark-expired', [RentedController::class, 'markExpired'])->name('admin.api.rented.mark-expired');
            Route::post('rented/{rented}/renew', [RentedController::class, 'renew'])->name('admin.api.rented.renew');
            Route::post('rented/{rented}/end', [RentedController::class, 'end'])->name('admin.api.rented.end');
            Route::get('rented-statistics', [RentedController::class, 'statistics'])->name('admin.api.rented.statistics');
            Route::get('properties/{property}/rate', [RentedController::class, 'getPropertyRate'])->name('admin.api.properties.rate');
            Route::post('rented/validate', [RentedController::class, 'validateRental'])->name('admin.api.rented.validate');
            Route::post('properties/sync-statuses', [RentedController::class, 'syncPropertyStatuses'])->name('admin.api.properties.sync-statuses');
        });
    });

    // API endpoint to get current user with roles
    Route::get('api/user', function () {
        return response()->json(auth()->user()->load('roles.permissions'));
    })->name('api.user');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
