<?php

use App\Http\Controllers\Api\Webhook\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks/payment')->group(function () {
    Route::get('vnpay/ipn', [PaymentController::class, 'vnpayIpn']);
});

// For browser redirection
Route::get('payment/vnpay/return', [PaymentController::class, 'vnpayReturn']);
