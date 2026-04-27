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
use App\Http\Controllers\Api\Admin\BlogCategoryController;
use App\Http\Controllers\Api\Admin\FaqManagementController;
use App\Http\Controllers\Api\Admin\FaqCategoryController;
use App\Http\Controllers\Api\Admin\MediaUploadController;
use App\Http\Controllers\Api\Admin\NotificationTemplateController;
use App\Http\Controllers\Api\Admin\ReviewController;
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
Route::post('/auth/forgot-password', [LoginController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [LoginController::class, 'resetPassword']);
Route::post('/auth/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [LoginController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/change-password', [LoginController::class, 'changePassword'])->middleware('auth:sanctum');

// Admin API
Route::prefix('admin')->middleware(['auth:sanctum', CheckRole::class . ':admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/bookings', [BookingManagementController::class, 'index']);
    Route::get('/bookings/export', [BookingManagementController::class, 'export']);
    Route::get('/bookings/{id}', [BookingManagementController::class, 'show']);
    Route::post('/bookings/{id}/mark-paid', [BookingManagementController::class, 'markPaid']);
    Route::post('/bookings/{id}/extend', [BookingManagementController::class, 'extend']);
    Route::delete('/bookings/{id}', [BookingManagementController::class, 'destroy']);

    Route::post('/lockers/reorder', [LockerController::class, 'reorder']);
    Route::post('/lockers/sync-all', [LockerController::class, 'syncAll']);
    Route::apiResource('lockers', LockerController::class)->names('admin.lockers');
    Route::post('/lockers/{id}/remote-unlock', [LockerController::class, 'remoteUnlock']);
    Route::post('/lockers/{id}/remote-lock', [LockerController::class, 'remoteLock']);
    Route::post('/lockers/{id}/sync', [LockerController::class, 'sync']);
    Route::post('/lockers/{id}/rename', [LockerController::class, 'rename']);
    Route::get('/lockers/{id}/passcodes', [LockerController::class, 'passcodesIndex']);
    Route::post('/lockers/{id}/passcodes', [LockerController::class, 'passcodeStore']);
    Route::put('/lockers/{id}/passcodes/{pwdId}', [LockerController::class, 'passcodeUpdate']);
    Route::delete('/lockers/{id}/passcodes/{pwdId}', [LockerController::class, 'passcodeDestroy']);
    Route::get('/lockers/{id}/unlock-records', [LockerController::class, 'unlockRecords']);

    Route::apiResource('locations', LocationManagementController::class)->names('admin.locations');
    Route::apiResource('pricing-rules', PricingRuleController::class)->names('admin.pricing-rules');
    Route::apiResource('blog-posts', BlogManagementController::class)->names('admin.blog-posts');
    Route::post('blog-categories/reorder', [BlogCategoryController::class, 'reorder'])->name('admin.blog-categories.reorder');
    Route::apiResource('blog-categories', BlogCategoryController::class)->except(['show'])->names('admin.blog-categories');
    Route::post('uploads', [MediaUploadController::class, 'store'])->name('admin.uploads.store');
    Route::post('faqs/reorder', [FaqManagementController::class, 'reorder'])->name('admin.faqs.reorder');
    Route::apiResource('faqs', FaqManagementController::class)->names('admin.faqs');
    Route::post('faq-categories/reorder', [FaqCategoryController::class, 'reorder'])->name('admin.faq-categories.reorder');
    Route::apiResource('faq-categories', FaqCategoryController::class)->except(['show'])->names('admin.faq-categories');
    Route::post('reviews/reorder', [ReviewController::class, 'reorder'])->name('admin.reviews.reorder');
    Route::post('reviews/{id}/approve', [ReviewController::class, 'approve'])->name('admin.reviews.approve');
    Route::post('reviews/{id}/reject', [ReviewController::class, 'reject'])->name('admin.reviews.reject');
    Route::apiResource('reviews', ReviewController::class)->names('admin.reviews');
    Route::apiResource('notification-templates', NotificationTemplateController::class)->names('admin.notification-templates');

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Super admin only
    Route::middleware(CheckRole::class . ':super_admin')->group(function () {
        Route::apiResource('users', UserController::class)->names('admin.users');
    });
});
