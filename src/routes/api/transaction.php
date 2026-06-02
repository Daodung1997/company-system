<?php

use App\Http\Controllers\Api\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('{id}', [TransactionController::class, 'show']);
        Route::put('{id}', [TransactionController::class, 'update']);
        Route::delete('{id}', [TransactionController::class, 'destroy']);
    });
});
