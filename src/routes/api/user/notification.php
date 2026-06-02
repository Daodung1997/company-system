<?php

use App\Http\Controllers\Api\User\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user/notifications', 'middleware' => 'auth:api'], function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::put('/read-all', [NotificationController::class, 'readAll']);
    Route::put('/{id}/read', [NotificationController::class, 'read']);
    Route::post('/device-token', [NotificationController::class, 'registerDeviceToken']);
    Route::delete('/', [NotificationController::class, 'destroy']);
});
