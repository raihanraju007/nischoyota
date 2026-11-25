<?php

use App\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

function registerExampleModule(): void
{
    app()->register(new class(app()) extends ModuleServiceProvider
    {
        public function boot(): void
        {
            $this->loadModuleRoutes(base_path('tests/Fixtures/ExampleModule/routes'));
        }
    });

    Route::getRoutes()->refreshNameLookups();
}

it('registers a module\'s storefront routes', function () {
    registerExampleModule();

    expect(route('example-module.index', absolute: false))->toBe('/example-module');

    $this->get('/example-module')->assertOk()->assertSee('storefront route');
});

it('registers a module\'s admin routes under /admin with admin. names', function () {
    registerExampleModule();

    expect(route('admin.example-module.index', absolute: false))->toBe('/admin/example-module');

    actingAsAdmin()->get('/admin/example-module')->assertOk()->assertSee('admin route');
});

it('protects module admin routes with auth and admin middleware', function () {
    registerExampleModule();

    expect(Route::getRoutes()->getByName('example-module.index')->gatherMiddleware())->toBe(['web'])
        ->and(Route::getRoutes()->getByName('admin.example-module.index')->gatherMiddleware())->toBe(['web', 'auth', 'admin']);

    $this->get('/admin/example-module')->assertRedirect(route('admin.login'));
});

it('renders a flashed message as a toast on the next page', function (string $sessionKey, string $toastType) {
    $this->withSession([$sessionKey => 'Category saved.'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-flash-toast="Category saved." data-flash-toast-type="'.$toastType.'"', escape: false);
})->with([
    ['success', 'success'],
    ['error', 'danger'],
    ['info', 'info'],
]);

it('renders flash toasts in the admin layout', function () {
    actingAsAdmin()->withSession(['success' => 'Saved.'])
        ->get(route('admin.dashboard'))
        ->assertSee('data-flash-toast="Saved."', escape: false);
});

it('renders flash toasts in the admin login layout', function () {
    $this->withSession(['success' => 'Saved.'])
        ->get(route('admin.login'))
        ->assertSee('data-flash-toast="Saved."', escape: false);
});

it('escapes html in flashed messages', function () {
    $this->withSession(['success' => '<script>alert(1)</script>'])
        ->get(route('home'))
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('renders a money amount', function () {
    expect(Blade::render('<x-money :amount="485050" />'))->toContain('৳ 4,850.50');
});

it('prevents lazy loading and silently discarded attributes outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue();
});
