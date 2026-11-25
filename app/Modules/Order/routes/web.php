<?php

use App\Modules\Order\Http\Controllers\Account\OrderController;
use App\Modules\Order\Http\Controllers\CheckoutController;
use App\Modules\Order\Http\Controllers\TrackOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
Route::get('/checkout/verify', [CheckoutController::class, 'verifyForm'])->name('checkout.verify');
Route::post('/checkout/verify', [CheckoutController::class, 'verify'])->name('checkout.verify.store')->middleware('throttle:10,1');
Route::post('/checkout/verify/resend', [CheckoutController::class, 'resend'])->name('checkout.verify.resend')->middleware('throttle:5,1');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/delivery-quote', [CheckoutController::class, 'deliveryQuote'])->name('checkout.delivery-quote');

Route::get('/track-order', [TrackOrderController::class, 'show'])->name('orders.track');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])->name('orders.track.lookup')->middleware('throttle:10,1');

Route::prefix('account/orders')->name('account.orders')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('');
    Route::get('/{orderNumber}', [OrderController::class, 'show'])->name('.show');
    Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('.cancel');
    Route::post('/{orderNumber}/buy-again', [OrderController::class, 'buyAgain'])->name('.buy-again');
});
