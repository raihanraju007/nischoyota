<?php

use App\Modules\Review\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/product/{slug}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'active', 'throttle:10,1'])
    ->name('products.reviews.store');
