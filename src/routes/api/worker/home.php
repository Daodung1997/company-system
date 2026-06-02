<?php

use App\Http\Controllers\Worker\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::group(['prefix' => 'home'], function () {
        Route::get('/', [HomeController::class, 'getHome']);
        Route::post('toggle-status', [HomeController::class, 'toggleStatus']);
    });
});
