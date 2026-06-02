<?php

use App\Http\Controllers\Api\ServiceCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('service-categories.index');
