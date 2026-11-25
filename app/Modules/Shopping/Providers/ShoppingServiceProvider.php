<?php

namespace App\Modules\Shopping\Providers;

use App\Modules\Shopping\Listeners\MergeGuestCartOnLogin;
use App\Providers\ModuleServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

final class ShoppingServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');

        Event::listen(Login::class, MergeGuestCartOnLogin::class);
    }
}
