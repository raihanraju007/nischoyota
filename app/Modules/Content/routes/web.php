<?php

use App\Modules\Content\Http\Controllers\ContactController;
use App\Modules\Content\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
| System pages keep their own URLs (route name => slug); extra pages live at /page/{slug} (F-020).
*/
foreach ([
    'about' => 'about',
    'faq' => 'faq',
    'privacy' => 'privacy-policy',
    'terms' => 'terms',
    'shipping' => 'shipping-policy',
    'returns' => 'return-policy',
] as $routeName => $slug) {
    Route::get("/{$slug}", [PageController::class, 'system'])->defaults('slug', $slug)->name($routeName);
}

Route::get('/page/{slug}', [PageController::class, 'show'])->name('page');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');
