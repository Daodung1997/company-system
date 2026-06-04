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
        
        // Restricted to users with view-timesheets permission (ADMIN, MANAGER, HR, ACCOUNTANT)
        Route::get('manage', [TimesheetController::class, 'manage'])->middleware('permission:view-timesheets');
        Route::get('statistics', [TimesheetController::class, 'statistics'])->middleware('permission:view-timesheets');
        
        // Payroll (ADMIN, MANAGER, ACCOUNTANT)
        Route::get('payroll', [TimesheetController::class, 'getPayroll'])->middleware('permission:view-payslips');
        Route::get('payroll/export-excel', [TimesheetController::class, 'exportPayrollExcel'])->middleware('permission:view-payslips');
        Route::get('payroll/export-pdf', [TimesheetController::class, 'exportPayrollPdf'])->middleware('permission:view-payslips');
        Route::post('payroll', [TimesheetController::class, 'savePayroll'])->middleware('permission:create-payslips');
        
        // Manual entry (ADMIN, MANAGER, HR)
        Route::post('store-manual', [TimesheetController::class, 'storeManual'])->middleware('permission:approve-timesheets');
        
        // Working Hour Configurations (ADMIN, MANAGER, HR)
        Route::get('working-hour-configs', [TimesheetController::class, 'listWorkingHourConfigs'])->middleware('permission:view-timesheets');
        Route::post('working-hour-configs', [TimesheetController::class, 'storeWorkingHourConfig'])->middleware('permission:approve-timesheets');
        Route::delete('working-hour-configs/{id}', [TimesheetController::class, 'deleteWorkingHourConfig'])->middleware('permission:approve-timesheets');

        // Employee Shifts (ADMIN, MANAGER, HR)
        Route::get('employee-shifts', [TimesheetController::class, 'listEmployeeShifts'])->middleware('permission:view-timesheets');
        Route::get('employee-shifts/calendar', [TimesheetController::class, 'listEmployeeShiftsCalendar'])->middleware('permission:view-timesheets');
        Route::post('employee-shifts', [TimesheetController::class, 'storeEmployeeShift'])->middleware('permission:approve-timesheets');
        Route::post('employee-shifts/reset', [TimesheetController::class, 'resetEmployeeShifts'])->middleware('permission:approve-timesheets');
        Route::delete('employee-shifts/{id}', [TimesheetController::class, 'deleteEmployeeShift'])->middleware('permission:approve-timesheets');
    });

    // Leave Requests
    Route::prefix('leave-requests')->group(function () {
        // Personal list and submit
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/', [LeaveRequestController::class, 'store']);
        
        // Approval list and action (ADMIN, MANAGER, HR)
        Route::get('pending', [LeaveRequestController::class, 'listPending'])->middleware('permission:view-leave-requests');
        Route::post('{id}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:approve-leave-requests');
    });
});
