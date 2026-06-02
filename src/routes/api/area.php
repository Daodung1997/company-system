<?php

use App\Http\Controllers\Api\AreaController;
use Illuminate\Support\Facades\Route;

Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
