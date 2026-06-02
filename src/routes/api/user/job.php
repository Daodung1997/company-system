<?php

use App\Http\Controllers\Api\User\JobController;
use App\Http\Controllers\Api\User\WorkerJobController;
use Illuminate\Support\Facades\Route;

// Customer Job Routes
Route::prefix('customer/jobs')->middleware(['auth:api'])->group(function () {
    Route::post('/', [JobController::class, 'store']);
    Route::get('/', [JobController::class, 'index']);
    Route::get('/{id}', [JobController::class, 'show']);
    Route::post('/{id}/cancel', [JobController::class, 'cancel']);
    Route::get('/{id}/quotations', [JobController::class, 'quotations']);
    Route::post('/{id}/quotations/{quotationId}/accept', [JobController::class, 'acceptQuotation']);
    Route::post('/{id}/quotations/{quotationId}/reject', [JobController::class, 'rejectQuotation']);
    Route::post('/{id}/complaint', [JobController::class, 'submitComplaint']);
    Route::post('/{id}/review', [JobController::class, 'reviewWorker']);
});

// Customer views public worker profile
Route::prefix('customer/workers')->middleware(['auth:api'])->group(function () {
    Route::get('/{id}', [JobController::class, 'showWorkerProfile']);
});

// Worker Job Routes
Route::prefix('worker/jobs')->middleware(['auth:api'])->group(function () {
    Route::get('/available', [WorkerJobController::class, 'availableJobs']);
    Route::get('/', [WorkerJobController::class, 'index']);
    Route::get('/{id}', [WorkerJobController::class, 'show']);
    Route::post('/{id}/quotation', [WorkerJobController::class, 'submitQuotation']);
    Route::post('/{id}/start', [WorkerJobController::class, 'start']);
    Route::post('/{id}/complete', [WorkerJobController::class, 'complete']);
    Route::post('/{id}/reject', [WorkerJobController::class, 'reject']);
    Route::post('/{id}/complaints/{complaintId}/reply', [WorkerJobController::class, 'replyComplaint']);
});
