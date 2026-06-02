<?php

use App\Http\Controllers\Api\Employee\EmployeeController;
use App\Http\Controllers\Api\Employee\EmployeeRelativeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('employees')->group(function () {
    // Employee CRUD
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    Route::post('/{id}/documents', [EmployeeController::class, 'uploadDocument']);

    // Employee Relatives (nested)
    Route::get('/{id}/relatives', [EmployeeRelativeController::class, 'index']);
    Route::post('/{id}/relatives', [EmployeeRelativeController::class, 'store']);
    Route::put('/{id}/relatives/{relativeId}', [EmployeeRelativeController::class, 'update']);
    Route::delete('/{id}/relatives/{relativeId}', [EmployeeRelativeController::class, 'destroy']);
});
