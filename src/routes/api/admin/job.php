<?php

use App\Constants\Commons\CommonPermissionConst;
use App\Http\Controllers\Api\Admin\JobController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('jobs', [JobController::class, 'index'])->middleware('permission:'.CommonPermissionConst::JOBS_VIEW);
    Route::get('jobs/{id}', [JobController::class, 'show'])->middleware('permission:'.CommonPermissionConst::JOBS_VIEW);
    Route::post('jobs/{id}/resolve/complete', [JobController::class, 'resolveComplete'])->middleware('permission:'.CommonPermissionConst::JOBS_RESOLVE);
    Route::post('jobs/{id}/resolve/refund', [JobController::class, 'resolveRefund'])->middleware('permission:'.CommonPermissionConst::JOBS_RESOLVE);
    Route::get('jobs/{id}/notes', [JobController::class, 'listNotes'])->middleware('permission:'.CommonPermissionConst::JOBS_VIEW);
    Route::post('jobs/{id}/notes', [JobController::class, 'addNote'])->middleware('permission:'.CommonPermissionConst::JOBS_VIEW);
});
