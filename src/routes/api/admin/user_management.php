<?php

use App\Constants\Commons\CommonPermissionConst;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->middleware('permission:'.CommonPermissionConst::ADMINS_VIEW);
        Route::post('/', [AdminController::class, 'store'])->middleware('permission:'.CommonPermissionConst::ADMINS_CREATE);
        Route::get('/{id}', [AdminController::class, 'show'])->middleware('permission:'.CommonPermissionConst::ADMINS_VIEW);
        Route::put('/{id}', [AdminController::class, 'update'])->middleware('permission:'.CommonPermissionConst::ADMINS_UPDATE);
        Route::post('/{id}/toggle-status', [AdminController::class, 'toggleStatus'])->middleware('permission:'.CommonPermissionConst::ADMINS_UPDATE);
        Route::delete('/{id}', [AdminController::class, 'destroy'])->middleware('permission:'.CommonPermissionConst::ADMINS_DELETE);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:'.CommonPermissionConst::ROLES_VIEW);
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:'.CommonPermissionConst::ROLES_CREATE);
        Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:'.CommonPermissionConst::ROLES_VIEW);
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:'.CommonPermissionConst::ROLES_UPDATE);
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:'.CommonPermissionConst::ROLES_DELETE);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [RoleController::class, 'getPermissions'])->middleware('permission:'.CommonPermissionConst::ROLES_VIEW);
    });
});
