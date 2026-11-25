<?php

use App\Modules\Location\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('locations')->name('locations.')->middleware('throttle:60,1')->group(function () {
    Route::get('/divisions/{division}/districts', [LocationController::class, 'districts'])->name('districts');
    Route::get('/districts/{district}/upazilas', [LocationController::class, 'upazilas'])->name('upazilas');
});
