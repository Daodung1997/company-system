<?php

use App\Constants\Commons\CommonPermissionConst;
use App\Http\Controllers\Api\Admin\Finance\PaymentController;
use App\Http\Controllers\Api\Admin\Finance\StatisticController;
use App\Http\Controllers\Api\Admin\Finance\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/finance')->middleware(['auth:admin'])->group(function () {
    // Payments
    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:'.CommonPermissionConst::FINANCE_PAYMENTS_VIEW);
    Route::get('payments/{id}', [PaymentController::class, 'show'])->middleware('permission:'.CommonPermissionConst::FINANCE_PAYMENTS_VIEW);
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund'])->middleware('permission:'.CommonPermissionConst::FINANCE_PAYMENTS_REFUND);

    // Withdrawals
    Route::get('withdrawals', [WithdrawalController::class, 'index'])->middleware('permission:'.CommonPermissionConst::FINANCE_WITHDRAWALS_VIEW);
    Route::get('withdrawals/{id}', [WithdrawalController::class, 'show'])->middleware('permission:'.CommonPermissionConst::FINANCE_WITHDRAWALS_VIEW);

    // Statistics
    Route::get('statistics/profit', [StatisticController::class, 'profit'])->middleware('permission:'.CommonPermissionConst::FINANCE_STATISTICS_VIEW);
    Route::get('statistics/cash-flow', [StatisticController::class, 'cashFlow'])->middleware('permission:'.CommonPermissionConst::FINANCE_STATISTICS_VIEW);
    Route::get('statistics/service-revenue', [StatisticController::class, 'serviceRevenue'])->middleware('permission:'.CommonPermissionConst::FINANCE_STATISTICS_VIEW);
});
