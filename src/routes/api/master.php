<?php

use App\Http\Controllers\Api\Master\DepartmentController;
use App\Http\Controllers\Api\Master\CompanySettingController;
use Illuminate\Support\Facades\Route;

// Public Master Routes
Route::prefix('master')->group(function () {
    Route::get('company-setting', [CompanySettingController::class, 'show']);
});

// Protected Master Routes (Require Authentication)
Route::middleware(['auth:api'])->prefix('master')->group(function () {
    Route::post('company-setting', [CompanySettingController::class, 'update']);

    // Department Master
    Route::prefix('department')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('create', [DepartmentController::class, 'store']);
        Route::get('{code}', [DepartmentController::class, 'show']);
        Route::put('{code}', [DepartmentController::class, 'update']);
        Route::delete('{code}', [DepartmentController::class, 'destroy']);
    });
});
