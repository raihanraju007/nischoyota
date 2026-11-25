<?php

namespace App\Modules\Order\Providers;

use App\Providers\ModuleServiceProvider;

final class OrderServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
