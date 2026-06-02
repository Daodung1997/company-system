<?php

use App\Http\Controllers\Api\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/auth')
    ->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh-token', [AuthController::class, 'refresh']);
        Route::middleware(['auth:admin'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });
