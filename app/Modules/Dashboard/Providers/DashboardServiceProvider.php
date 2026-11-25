<?php

namespace App\Modules\Dashboard\Providers;

use App\Providers\ModuleServiceProvider;

final class DashboardServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
