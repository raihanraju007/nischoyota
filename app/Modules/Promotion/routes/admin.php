<?php

use App\Modules\Promotion\Http\Controllers\Admin\CouponController;
use Illuminate\Support\Facades\Route;

/*
| Coupons are admin-only (ADR-012).
*/
Route::middleware('can:manage-store')->group(function () {
    Route::resource('coupons', CouponController::class)->except(['show']);
});
