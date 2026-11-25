<?php

use App\Modules\Order\Http\Controllers\Admin\OrderController;
use App\Modules\Order\Http\Controllers\Admin\OrderPrintController;
use Illuminate\Support\Facades\Route;

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::post('orders/packing-slips', [OrderPrintController::class, 'bulkPackingSlips'])->name('orders.packing-slips');
Route::get('orders/{order:order_number}', [OrderController::class, 'show'])->name('orders.show');
Route::post('orders/{order:order_number}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
Route::post('orders/{order:order_number}/payment', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
Route::put('orders/{order:order_number}/note', [OrderController::class, 'updateNote'])->name('orders.note');
Route::get('orders/{order:order_number}/invoice', [OrderPrintController::class, 'invoice'])->name('orders.invoice');
Route::get('orders/{order:order_number}/packing-slip', [OrderPrintController::class, 'packingSlip'])->name('orders.packing-slip');
