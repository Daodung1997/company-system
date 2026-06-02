<?php

use App\Http\Controllers\Api\Compliance\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('compliance')->group(function () {
        Route::get('/', [ComplianceController::class, 'index']);
        Route::post('scan', [ComplianceController::class, 'scan']);
        Route::put('{id}/resolve', [ComplianceController::class, 'resolve']);
    });
});
