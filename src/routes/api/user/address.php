<?php

use App\Http\Controllers\Api\User\UserAddressController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user/addresses', 'middleware' => ['auth:api']], function () {
    Route::get('/', [UserAddressController::class, 'index'])->name('user.addresses.index');
    Route::post('/', [UserAddressController::class, 'store'])->name('user.addresses.store');
    Route::put('/{id}', [UserAddressController::class, 'update'])->name('user.addresses.update');
    Route::delete('/{id}', [UserAddressController::class, 'destroy'])->name('user.addresses.destroy');
});
