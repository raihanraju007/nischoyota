<?php

use App\Modules\Account\Http\Controllers\Admin\CustomerController;
use App\Modules\Account\Http\Controllers\Admin\LoginController;
use App\Modules\Account\Http\Controllers\Admin\PasswordController;
use Illuminate\Support\Facades\Route;

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/{customerId}', [CustomerController::class, 'show'])->whereNumber('customerId')->name('customers.show');
Route::post('/customers/{customerId}/block', [CustomerController::class, 'block'])->whereNumber('customerId')->name('customers.block');
Route::post('/customers/{customerId}/unblock', [CustomerController::class, 'unblock'])->whereNumber('customerId')->name('customers.unblock');
