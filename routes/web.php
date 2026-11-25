<?php

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Every page is served by a module: storefront routes live in
| app/Modules/<Module>/routes/web.php and admin routes in
| app/Modules/<Module>/routes/admin.php (see docs/architecture.md).
|
*/

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::post('/language/{locale}', [LocaleController::class, 'update'])->name('locale.update');
