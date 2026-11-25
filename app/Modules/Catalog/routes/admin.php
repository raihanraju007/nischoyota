<?php

use App\Modules\Catalog\Http\Controllers\Admin\AttributeController;
use App\Modules\Catalog\Http\Controllers\Admin\AttributeValueController;
use App\Modules\Catalog\Http\Controllers\Admin\BrandController;
use App\Modules\Catalog\Http\Controllers\Admin\CategoryController;
use App\Modules\Catalog\Http\Controllers\Admin\ProductController;
use App\Modules\Catalog\Http\Controllers\Admin\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
Route::resource('products', ProductController::class)->except(['show']);
Route::get('products/{product}/variants', [ProductVariantController::class, 'edit'])->name('products.variants.edit');
Route::post('products/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('products.variants.generate');
Route::put('products/{product}/variants', [ProductVariantController::class, 'update'])->name('products.variants.update');
Route::resource('categories', CategoryController::class)->except(['create', 'show']);
Route::resource('brands', BrandController::class)->except(['create', 'show']);
Route::resource('attributes', AttributeController::class)->except(['create', 'show']);

Route::scopeBindings()->prefix('attributes/{attribute}/values')->name('attributes.values.')->group(function () {
    Route::post('/', [AttributeValueController::class, 'store'])->name('store');
    Route::put('/{value}', [AttributeValueController::class, 'update'])->name('update');
    Route::delete('/{value}', [AttributeValueController::class, 'destroy'])->name('destroy');
});
