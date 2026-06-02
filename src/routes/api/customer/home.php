<?php

use App\Http\Controllers\Customer\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::get('home', [HomeController::class, 'getHome']);
});
