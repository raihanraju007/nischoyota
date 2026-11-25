<?php

namespace App\Modules\Inventory\Providers;

use App\Providers\ModuleServiceProvider;

final class InventoryServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
