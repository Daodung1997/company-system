<?php

use App\Http\Controllers\Api\User\ReviewController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function () {
    // Customer creates review
    Route::post('/reviews', [ReviewController::class, 'create'])->name('user.reviews.create');
});

Route::group(['prefix' => 'worker', 'middleware' => ['auth:api']], function () {
    // Worker view my reviews
    Route::get('/reviews', [ReviewController::class, 'listMyReviews'])->name('worker.reviews.list');
    Route::get('/reviews/summary', [ReviewController::class, 'summary'])->name('worker.reviews.summary');
});

Route::group(['prefix' => 'public'], function () {
    // Public view worker reviews
    Route::get('/workers/{id}/reviews', [ReviewController::class, 'listPublicWorkerReviews'])->name('public.worker.reviews.list');
});
