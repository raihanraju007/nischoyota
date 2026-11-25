<?php

use App\Modules\Review\Http\Controllers\Admin\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
