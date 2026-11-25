<?php

namespace App\Modules\Catalog\Providers;

use App\Providers\ModuleServiceProvider;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
