<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base provider for every module in app/Modules. Each module's own provider extends this
 * class, calls loadModuleRoutes() in boot(), and is listed in bootstrap/providers.php.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register the module's routes/web.php (storefront) and routes/admin.php (under /admin, named admin.*,
     * signed-in active admin/staff only).
     */
    protected function loadModuleRoutes(string $routesDirectory): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $storefrontRoutes = $routesDirectory.'/web.php';
        $adminRoutes = $routesDirectory.'/admin.php';

        if (is_file($storefrontRoutes)) {
            Route::middleware('web')->group($storefrontRoutes);
        }

        if (is_file($adminRoutes)) {
            Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->name('admin.')->group($adminRoutes);
        }
    }
}
