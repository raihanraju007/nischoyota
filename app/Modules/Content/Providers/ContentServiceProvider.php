<?php

namespace App\Modules\Content\Providers;

use App\Providers\ModuleServiceProvider;

final class ContentServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
