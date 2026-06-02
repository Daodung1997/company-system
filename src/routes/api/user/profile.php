<?php

use App\Http\Controllers\Api\User\CustomerProfileController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer/profile', 'middleware' => ['auth:api']], function () {
    Route::get('/', [CustomerProfileController::class, 'show'])->name('customer.profile.show');
    Route::put('/', [CustomerProfileController::class, 'update'])->name('customer.profile.update');

    Route::put('/password', [CustomerProfileController::class, 'changePassword'])->name('customer.profile.password');
});
