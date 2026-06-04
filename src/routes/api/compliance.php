<?php

use App\Http\Controllers\Api\Compliance\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('compliance')->group(function () {
        Route::get('/', [ComplianceController::class, 'index'])->middleware('permission:view-compliance');
        Route::post('scan', [ComplianceController::class, 'scan'])->middleware('permission:view-compliance');
        Route::put('{id}/resolve', [ComplianceController::class, 'resolve'])->middleware('permission:view-compliance');
    });
});
