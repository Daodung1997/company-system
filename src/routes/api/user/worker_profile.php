<?php

use App\Http\Controllers\Api\User\WorkerProfileController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'worker/profile', 'middleware' => ['auth:api']], function () {
    Route::get('/', [WorkerProfileController::class, 'show'])->name('worker.profile.show');
    Route::put('/', [WorkerProfileController::class, 'update'])->name('worker.profile.update');
    Route::put('/availability', [WorkerProfileController::class, 'toggleAvailability'])->name('worker.profile.availability');
    Route::put('/areas', [WorkerProfileController::class, 'updateAreas'])->name('worker.profile.areas');
    Route::put('/services', [WorkerProfileController::class, 'updateServices'])->name('worker.profile.services');
});
