<?php

use App\Http\Controllers\Api\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('user/auth')
    ->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-verification-otp', [AuthController::class, 'resendVerificationOtp']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('refresh-token', [AuthController::class, 'refresh']);
        Route::middleware(['auth:api'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('choose-role', [AuthController::class, 'chooseRole']);
        });
    });
