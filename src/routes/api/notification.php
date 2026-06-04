<?php

use App\Http\Controllers\Api\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
    });
});
