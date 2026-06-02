<?php

use App\Http\Controllers\Api\Contract\ContractController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('{id}', [ContractController::class, 'show']);
        Route::get('{id}/export-pdf', [ContractController::class, 'exportPdf']);
        Route::put('{id}', [ContractController::class, 'update']);
        Route::delete('{id}', [ContractController::class, 'destroy']);
    });
});
