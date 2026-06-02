<?php

use App\Http\Controllers\Api\User\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/payment-methods', [PaymentController::class, 'listPaymentMethods']);

    Route::prefix('customer/jobs')->group(function () {
        Route::get('/{id}/payment', [PaymentController::class, 'getPaymentInfo']);
        Route::post('/{id}/payment', [PaymentController::class, 'createPayment']);
        Route::post('/{id}/payment/confirm', [PaymentController::class, 'confirmPayment']);
        Route::post('/{id}/payment/cash', [PaymentController::class, 'payCash']);
        Route::post('/{id}/payment/gateway', [PaymentController::class, 'createGatewayPayment']);
    });
});
