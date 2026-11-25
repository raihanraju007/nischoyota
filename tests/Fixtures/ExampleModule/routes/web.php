<?php

use Illuminate\Support\Facades\Route;

Route::get('/example-module', fn (): string => 'storefront route')->name('example-module.index');
