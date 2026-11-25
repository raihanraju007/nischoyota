<?php

use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\ProductPageController;
use App\Modules\Catalog\Http\Controllers\SearchController;
use App\Modules\Catalog\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/category/{slug?}', [ShopController::class, 'category'])->name('category');
Route::get('/deals', [ShopController::class, 'deals'])->name('deals');
Route::get('/product/{slug}', [ProductPageController::class, 'show'])->name('product');
Route::get('/search', [SearchController::class, 'index'])->name('search');
