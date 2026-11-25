<?php

namespace App\Modules\Account\Providers;

use App\Modules\Account\Console\CreateAdminCommand;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\AdminAuthenticationService;
use App\Modules\Account\Services\CustomerAuthService;
use App\Modules\Account\Sms\ArraySmsGateway;
use App\Modules\Account\Sms\LogSmsGateway;
use App\Modules\Account\Sms\SmsGateway;
use App\Providers\ModuleServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class AccountServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, fn (): SmsGateway => match (config('services.sms.driver')) {
            'array' => new ArraySmsGateway,
            default => new LogSmsGateway,
        });

        $this->app->when([AdminAuthenticationService::class, CustomerAuthService::class])
            ->needs(StatefulGuard::class)
            ->give(fn (): StatefulGuard => Auth::guard('web'));
    }

    public function boot(): void
    {
        $this->loadModuleRoutes(__DIR__.'/../routes');

        Gate::define('manage-store', fn (User $user): bool => $user->role->canManageStore());

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class]);
        }
    }
}
