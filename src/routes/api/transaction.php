<?php

use App\Http\Controllers\Api\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->middleware('permission:view-transactions');
        Route::post('/', [TransactionController::class, 'store'])->middleware('permission:create-transactions');
        Route::get('{id}', [TransactionController::class, 'show'])->middleware('permission:view-transactions');
        Route::put('{id}', [TransactionController::class, 'update'])->middleware('permission:update-transactions');
        Route::delete('{id}', [TransactionController::class, 'destroy'])->middleware('permission:delete-transactions');
    });
});
