<?php

use App\Http\Controllers\Api\User\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('user/chat')->group(function () {
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations/start', [ChatController::class, 'start']);
    Route::get('/conversations/{id}/messages', [ChatController::class, 'show']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'store']);
    Route::post('/media', [ChatController::class, 'upload']);
    Route::put('/conversations/{id}/read', [ChatController::class, 'update']);
});
