<?php

use App\Modules\Inventory\Http\Controllers\Admin\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('inventory/{variant}', [InventoryController::class, 'show'])->name('inventory.show');
Route::post('inventory/{variant}/adjustments', [InventoryController::class, 'adjust'])->name('inventory.adjust');
