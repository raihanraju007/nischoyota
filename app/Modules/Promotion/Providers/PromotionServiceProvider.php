<?php

namespace App\Modules\Promotion\Providers;

use App\Providers\ModuleServiceProvider;

final class PromotionServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
