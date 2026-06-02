<?php

use App\Http\Controllers\Api\Master\CompanyController;
use App\Http\Controllers\Api\Master\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('master')->group(function () {
    // Company Master
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('create', [CompanyController::class, 'store']);
        Route::get('{code}', [CompanyController::class, 'show']);
        Route::put('{code}', [CompanyController::class, 'update']);
        Route::delete('{code}', [CompanyController::class, 'destroy']);
    });

    // Department Master
    Route::prefix('department')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('create', [DepartmentController::class, 'store']);
        Route::get('{code}', [DepartmentController::class, 'show']);
        Route::put('{code}', [DepartmentController::class, 'update']);
        Route::delete('{code}', [DepartmentController::class, 'destroy']);
    });
});
