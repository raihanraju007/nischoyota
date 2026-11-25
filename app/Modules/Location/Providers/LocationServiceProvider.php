<?php

namespace App\Modules\Location\Providers;

use App\Providers\ModuleServiceProvider;

final class LocationServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
