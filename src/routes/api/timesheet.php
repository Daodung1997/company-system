<?php

use App\Http\Controllers\Api\Timesheet\TimesheetController;
use App\Http\Controllers\Api\Timesheet\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    // Timesheets
    Route::prefix('timesheets')->group(function () {
        Route::get('monthly', [TimesheetController::class, 'monthly']);
        Route::post('check-in', [TimesheetController::class, 'checkIn']);
        Route::post('check-out', [TimesheetController::class, 'checkOut']);
        Route::get('manage', [TimesheetController::class, 'manage']);
        Route::get('statistics', [TimesheetController::class, 'statistics']);
        Route::post('store-manual', [TimesheetController::class, 'storeManual']);
        
        // Working Hour Configurations
        Route::get('working-hour-configs', [TimesheetController::class, 'listWorkingHourConfigs']);
        Route::post('working-hour-configs', [TimesheetController::class, 'storeWorkingHourConfig']);
        Route::delete('working-hour-configs/{id}', [TimesheetController::class, 'deleteWorkingHourConfig']);
    });

    // Leave Requests
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/', [LeaveRequestController::class, 'store']);
        Route::get('pending', [LeaveRequestController::class, 'listPending']);
        Route::post('{id}/approve', [LeaveRequestController::class, 'approve']);
    });
});
