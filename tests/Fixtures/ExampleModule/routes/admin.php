<?php

use Illuminate\Support\Facades\Route;

Route::get('/example-module', fn (): string => 'admin route')->name('example-module.index');
