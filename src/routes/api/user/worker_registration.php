<?php

use App\Http\Controllers\Api\User\WorkerRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('worker/registration')->middleware(['auth:api'])->group(function () {
    Route::post('/', [WorkerRegistrationController::class, 'submit']);

    Route::get('/status', [WorkerRegistrationController::class, 'getStatus']);
    Route::put('/', [WorkerRegistrationController::class, 'resubmit']);
});
