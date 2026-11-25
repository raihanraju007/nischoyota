<?php

namespace App\Modules\Review\Providers;

use App\Providers\ModuleServiceProvider;

final class ReviewServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');
    }
}
