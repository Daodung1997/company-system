<?php

use App\Http\Controllers\Api\Document\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::post('/attach', [DocumentController::class, 'attach']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
        Route::get('/{id}/download', [DocumentController::class, 'download']);
    });
});
