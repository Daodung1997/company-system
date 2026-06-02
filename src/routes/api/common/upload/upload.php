<?php

use App\Http\Controllers\Common\UploadController;
use Illuminate\Support\Facades\Route;

Route::controller(UploadController::class)
    ->prefix('upload')
    ->group(function () {
        Route::post('/images', 'uploadImages');
        Route::post('/image', 'uploadSingleImage');
        Route::post('/signature', 'uploadSignature');
    });
