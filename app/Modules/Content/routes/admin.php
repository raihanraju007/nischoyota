<?php

use App\Modules\Content\Http\Controllers\Admin\ContactMessageController;
use App\Modules\Content\Http\Controllers\Admin\PageController;
use App\Modules\Content\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('settings', [SettingsController::class, 'show'])->name('settings');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
Route::patch('messages/{message}/status', [ContactMessageController::class, 'updateStatus'])->name('messages.status');

/*
| Content pages are admin-only (ADR-012).
*/
Route::middleware('can:manage-store')->group(function () {
    Route::resource('pages', PageController::class)->except(['show']);
});
