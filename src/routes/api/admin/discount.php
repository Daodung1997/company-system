<?php

use App\Constants\Commons\CommonPermissionConst;
use App\Http\Controllers\Api\Admin\DiscountController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('discounts', [DiscountController::class, 'index'])->middleware('permission:'.CommonPermissionConst::PROMOTIONS_VIEW);
    Route::post('discounts', [DiscountController::class, 'store'])->middleware('permission:'.CommonPermissionConst::PROMOTIONS_CREATE);
    Route::get('discounts/{id}', [DiscountController::class, 'show'])->middleware('permission:'.CommonPermissionConst::PROMOTIONS_VIEW);
    Route::put('discounts/{id}', [DiscountController::class, 'update'])->middleware('permission:'.CommonPermissionConst::PROMOTIONS_UPDATE);
    Route::post('discounts/{id}/toggle-status', [DiscountController::class, 'toggleStatus'])->middleware('permission:'.CommonPermissionConst::PROMOTIONS_UPDATE);
});
