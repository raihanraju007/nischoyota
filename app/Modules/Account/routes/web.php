<?php

use App\Modules\Account\Http\Controllers\AccountDashboardController;
use App\Modules\Account\Http\Controllers\AddressController;
use App\Modules\Account\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Modules\Account\Http\Controllers\LoginController;
use App\Modules\Account\Http\Controllers\PasswordResetController;
use App\Modules\Account\Http\Controllers\PhoneChangeController;
use App\Modules\Account\Http\Controllers\PhoneVerificationController;
use App\Modules\Account\Http\Controllers\ProfileController;
use App\Modules\Account\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
});

/*
| Storefront customer authentication (F-014)
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/verify-phone', [PhoneVerificationController::class, 'show'])->name('phone.verify');
    Route::post('/verify-phone', [PhoneVerificationController::class, 'store'])->name('phone.verify.store')->middleware('throttle:10,1');
    Route::post('/verify-phone/resend', [PhoneVerificationController::class, 'resend'])->name('phone.verify.resend');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.email')->middleware('throttle:10,1');
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store')->middleware('throttle:10,1');
    Route::post('/reset-password/resend', [PasswordResetController::class, 'resend'])->name('password.resend');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

/*
| Customer account (F-015). Order pages are added in F-024.
*/
Route::prefix('account')->name('account.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', AccountDashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/phone', [PhoneChangeController::class, 'show'])->name('phone');
    Route::post('/phone', [PhoneChangeController::class, 'sendCode'])->name('phone.send-code');
    Route::post('/phone/verify', [PhoneChangeController::class, 'verify'])->name('phone.verify')->middleware('throttle:10,1');

    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses');
    Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit')->can('manage', 'address');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update')->can('manage', 'address');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy')->can('manage', 'address');
    Route::post('/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('addresses.default')->can('manage', 'address');
});
