<?php

use App\Modules\Location\Http\Controllers\Admin\DeliveryAreaController;
use App\Modules\Location\Http\Controllers\Admin\DeliveryZoneController;
use Illuminate\Support\Facades\Route;

/*
| Delivery zones and areas are admin-only (ADR-012).
*/
Route::middleware('can:manage-store')->group(function () {
    Route::resource('delivery-zones', DeliveryZoneController::class)->except(['create', 'show']);

    Route::get('delivery-areas', [DeliveryAreaController::class, 'index'])->name('delivery-areas.index');
    Route::put('delivery-areas', [DeliveryAreaController::class, 'update'])->name('delivery-areas.update');
    Route::get('delivery-areas/{district}', [DeliveryAreaController::class, 'show'])->name('delivery-areas.show');
    Route::put('delivery-areas/{district}', [DeliveryAreaController::class, 'updateDistrict'])->name('delivery-areas.update-district');
});
