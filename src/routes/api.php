<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/api/user/profile.php';
require __DIR__.'/api/auth.php';
require __DIR__.'/api/user/worker_profile.php';
require __DIR__.'/api/user/worker_registration.php';
require __DIR__.'/api/user/job.php';
require __DIR__.'/api/user/payment.php';
require __DIR__.'/api/user/review.php';
require __DIR__.'/api/user/wallet.php';
require __DIR__.'/api/user/notification.php';
require __DIR__.'/api/user/chat.php';
require __DIR__.'/api/user/address.php';
require __DIR__.'/api/user/discount.php';
require __DIR__.'/api/webhook.php';
require __DIR__.'/api/area.php';
require __DIR__.'/api/service_category.php';
require __DIR__.'/api/admin/finance.php';
require __DIR__.'/api/admin/job.php';
require __DIR__.'/api/admin/configuration.php';
require __DIR__.'/api/admin/user_management.php';
require __DIR__.'/api/admin/discount.php';
require __DIR__.'/api/employee.php';
require __DIR__.'/api/timesheet.php';
require __DIR__.'/api/contract.php';
require __DIR__.'/api/document.php';
require __DIR__.'/api/transaction.php';
require __DIR__.'/api/compliance.php';
require __DIR__.'/api/dashboard.php';
require __DIR__.'/api/master.php';
require __DIR__.'/api/notification.php';

Route::prefix('customer')->group(function () {
    require __DIR__.'/api/customer/home.php';
});

Route::prefix('worker')->group(function () {
    require __DIR__.'/api/worker/home.php';
});

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\User\AuthController as UserAuthController;
use App\Http\Controllers\Common\AuthWorkerController;

Route::prefix('user/auth')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('verify-email', [UserAuthController::class, 'verifyEmail']);
    Route::post('resend-verification-otp', [UserAuthController::class, 'resendVerificationOtp']);
    Route::post('login', [UserAuthController::class, 'login']);
    Route::post('forgot-password', [UserAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [UserAuthController::class, 'resetPassword']);
    Route::post('refresh-token', [UserAuthController::class, 'refresh']);
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', [UserAuthController::class, 'logout']);
        Route::post('choose-role', [UserAuthController::class, 'chooseRole']);
    });
});

Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('refresh-token', [AdminAuthController::class, 'refresh']);
    Route::middleware(['auth:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
    });
});

Route::prefix('worker/auth')->group(function () {
    Route::post('register', [AuthWorkerController::class, 'register']);
    Route::post('login', [AuthWorkerController::class, 'login']);
});

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    // Customer Management
    Route::get('customers', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'index'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CUSTOMERS_VIEW);
    Route::get('customers/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'show'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CUSTOMERS_VIEW);
    Route::put('customers/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'update'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CUSTOMERS_UPDATE);
    Route::post('customers/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'toggleStatus'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CUSTOMERS_BLOCK);

    // Worker Management
    Route::get('workers', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'index'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_VIEW);
    Route::get('workers/{id}', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'show'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_VIEW);
    Route::put('workers/{id}', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'update'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_UPDATE);
    Route::post('workers/{id}/approve', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'approve'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_APPROVE);
    Route::post('workers/{id}/reject', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'reject'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_REJECT);
    Route::post('workers/{id}/suspend', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'suspend'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_SUSPEND);
    Route::post('workers/{id}/activate', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'activate'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_ACTIVATE);
    Route::get('workers/{id}/registration-history', [\App\Http\Controllers\Api\Admin\WorkerController::class, 'registrationHistory'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::WORKERS_VIEW);

    // Configuration - Categories & Fees
    Route::prefix('config')->group(function () {
        Route::get('categories', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'index'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_VIEW);
        Route::post('categories', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'store'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_CREATE);
        Route::post('categories/reorder', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'reorder'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_UPDATE);
        Route::get('categories/{category}', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'show'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_VIEW);
        Route::put('categories/{category}', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'update'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_UPDATE);
        Route::delete('categories/{category}', [\App\Http\Controllers\Api\Admin\Configuration\ServiceCategoryController::class, 'destroy'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::CATEGORIES_DELETE);
        Route::get('fees', [\App\Http\Controllers\Api\Admin\Configuration\PlatformFeeController::class, 'index'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::FEES_VIEW);
        Route::post('fees', [\App\Http\Controllers\Api\Admin\Configuration\PlatformFeeController::class, 'store'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::FEES_UPDATE);
        Route::put('fees/{id}', [\App\Http\Controllers\Api\Admin\Configuration\PlatformFeeController::class, 'update'])->middleware('permission:'.\App\Constants\Commons\CommonPermissionConst::FEES_UPDATE);
    });
});

Route::middleware([
    'locale',
    'throttle:user-logined',
    'jwtClient.auth',
])
    ->prefix('common')
    ->group(function () {
        require __DIR__.'/api/common/upload/upload.php';
    });
Broadcast::routes(['middleware' => ['jwtClient.auth']]);
