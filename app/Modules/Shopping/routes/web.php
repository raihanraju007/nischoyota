<?php

use App\Modules\Shopping\Http\Controllers\CartController;
use App\Modules\Shopping\Http\Controllers\CartCouponController;
use App\Modules\Shopping\Http\Controllers\CartItemController;
use App\Modules\Shopping\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store')->middleware('throttle:60,1');
Route::patch('/cart/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update')->whereNumber('item');
Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy')->whereNumber('item');

Route::post('/cart/coupon', [CartCouponController::class, 'store'])->name('cart.coupon.store')->middleware('throttle:20,1');
Route::delete('/cart/coupon', [CartCouponController::class, 'destroy'])->name('cart.coupon.destroy');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle')->whereNumber('product')->middleware('throttle:60,1');
    Route::post('/wishlist/{product}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move')->whereNumber('product');
});
