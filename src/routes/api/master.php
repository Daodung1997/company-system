<?php

use App\Http\Controllers\Api\Master\DepartmentController;
use App\Http\Controllers\Api\Master\CompanySettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('master')->group(function () {
    // Company Setting
    Route::prefix('company-setting')->group(function () {
        Route::get('/', [CompanySettingController::class, 'show']);
        Route::post('/', [CompanySettingController::class, 'update']);
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
