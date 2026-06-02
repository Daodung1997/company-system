<?php

use App\Http\Controllers\Api\User\DiscountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::post('app/discounts/check', [DiscountController::class, 'check']);
});
