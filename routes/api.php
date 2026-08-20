<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PrivateEventController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\SupportController as ApiSupportController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'app' => 'Ekaadh',
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    });

    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/otp/send', [OtpController::class, 'send']);
        Route::post('/otp/verify', [OtpController::class, 'verify']);
    });

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{idOrSlug}', [EventController::class, 'show']);

    Route::prefix('support')->middleware('throttle:support')->group(function () {
        Route::get('/faqs', [ApiSupportController::class, 'faqs']);
        Route::post('/conversation', [ApiSupportController::class, 'conversation']);
        Route::get('/conversations/{conversation}/messages', [ApiSupportController::class, 'messages']);
        Route::post('/conversations/{conversation}/messages', [ApiSupportController::class, 'storeMessage']);
    });

    Route::middleware('throttle:checkout')->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'store']);
        Route::post('/orders/{orderNumber}/pay', [CheckoutController::class, 'pay']);
    });

    Route::middleware('throttle:tickets')->group(function () {
        Route::get('/orders/{orderNumber}', [CheckoutController::class, 'show']);
        Route::get('/tickets/{code}', [TicketController::class, 'show']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/password', [AuthController::class, 'changePassword']);
        Route::delete('/auth/account', [AuthController::class, 'destroyAccount']);
        Route::post('/auth/device-token', [DeviceTokenController::class, 'store']);
        Route::delete('/auth/device-token', [DeviceTokenController::class, 'destroy']);
        Route::get('/my-tickets', [TicketController::class, 'mine']);
        Route::get('/notifications', [ApiNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [ApiNotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [ApiNotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [ApiNotificationController::class, 'open']);

        Route::prefix('private-events')->group(function () {
            Route::get('/meta', [PrivateEventController::class, 'meta']);
            Route::get('/', [PrivateEventController::class, 'index']);
            Route::post('/', [PrivateEventController::class, 'store']);
            Route::get('/{event}', [PrivateEventController::class, 'show']);
            Route::post('/{event}/pay', [PrivateEventController::class, 'pay'])->middleware('throttle:checkout');
            Route::post('/{event}/add-tickets', [PrivateEventController::class, 'addCapacity']);
            Route::get('/{event}/invitations', [PrivateEventController::class, 'invitations']);
            Route::post('/{event}/invitations', [PrivateEventController::class, 'storeInvitations']);
            Route::post('/{event}/invitations/{invitation}/resend', [PrivateEventController::class, 'resendInvitation']);
            Route::post('/{event}/invitations/{invitation}/revoke', [PrivateEventController::class, 'revokeInvitation']);
        });

        Route::middleware(['active', 'role:staff,admin'])->prefix('staff')->group(function () {
            Route::get('/events', [CheckInController::class, 'events']);
            Route::get('/events/{event}/stats', [CheckInController::class, 'stats']);
            Route::post('/check-in', [CheckInController::class, 'scan'])->middleware('throttle:check-in');
        });
    });
});
