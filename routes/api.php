<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\Auth\GuestController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\BookingManagementController;
use App\Http\Controllers\Api\Admin\LockerController;
use App\Http\Controllers\Api\Admin\LocationManagementController;
use App\Http\Controllers\Api\Admin\PricingRuleController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\BlogManagementController;
use App\Http\Controllers\Api\Admin\FaqManagementController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Public API
Route::get('/locations/{id}/availability', [AvailabilityController::class, 'check']);
Route::get('/locations/{id}/pricing', [PricingController::class, 'calculate']);
Route::post('/bookings', [BookingApiController::class, 'store'])->middleware('auth:sanctum');
Route::post('/bookings/{uuid}/cancel', [BookingApiController::class, 'cancel']);

// Auth
Route::post('/auth/guest', [GuestController::class, 'register']);
Route::post('/auth/login', [LoginController::class, 'login']);
Route::post('/auth/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [LoginController::class, 'me'])->middleware('auth:sanctum');

// Admin API
Route::prefix('admin')->middleware(['auth:sanctum', CheckRole::class . ':admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/bookings', [BookingManagementController::class, 'index']);
    Route::get('/bookings/export', [BookingManagementController::class, 'export']);
    Route::get('/bookings/{id}', [BookingManagementController::class, 'show']);
    Route::post('/bookings/{id}/mark-paid', [BookingManagementController::class, 'markPaid']);
    Route::post('/bookings/{id}/extend', [BookingManagementController::class, 'extend']);
    Route::delete('/bookings/{id}', [BookingManagementController::class, 'destroy']);

    Route::apiResource('lockers', LockerController::class)->names('admin.lockers');
    Route::post('/lockers/{id}/remote-unlock', [LockerController::class, 'remoteUnlock']);

    Route::apiResource('locations', LocationManagementController::class)->names('admin.locations');
    Route::apiResource('pricing-rules', PricingRuleController::class)->names('admin.pricing-rules');
    Route::apiResource('blog-posts', BlogManagementController::class)->names('admin.blog-posts');
    Route::apiResource('faqs', FaqManagementController::class)->names('admin.faqs');

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Super admin only
    Route::middleware(CheckRole::class . ':super_admin')->group(function () {
        Route::apiResource('users', UserController::class)->names('admin.users');
    });
});
