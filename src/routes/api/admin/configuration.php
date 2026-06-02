<?php

use App\Constants\Commons\CommonPermissionConst;
use App\Http\Controllers\Api\Admin\Configuration\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/config', 'middleware' => ['auth:admin']], function () {
    Route::get('/job-assignment', [ConfigurationController::class, 'getJobAssignmentConfig'])->middleware('permission:'.CommonPermissionConst::CONFIGURATION_VIEW);
    Route::put('/job-assignment', [ConfigurationController::class, 'updateJobAssignmentConfig'])->middleware('permission:'.CommonPermissionConst::CONFIGURATION_UPDATE);
});
