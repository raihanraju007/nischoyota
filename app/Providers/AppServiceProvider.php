<?php

namespace App\Providers;

use App\Http\View\Composers\AdminShellComposer;
use App\Http\View\Composers\CartDrawerComposer;
use App\Http\View\Composers\HomeReviewsComposer;
use App\Http\View\Composers\ProductReviewsComposer;
use App\Http\View\Composers\StorefrontShellComposer;
use App\Http\View\Composers\WishlistHeartComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModels();

        Paginator::defaultView('partials.pagination');

        View::composer(['components.layout.header', 'components.layout.footer', 'components.layout.mobile-menu', 'components.layout.bottom-nav'], StorefrontShellComposer::class);
        View::composer('components.layout.cart-drawer', CartDrawerComposer::class);
        View::composer(['components.product-card', 'products.show'], WishlistHeartComposer::class);
        View::composer('components.layouts.admin', AdminShellComposer::class);
        View::composer('products.show', ProductReviewsComposer::class);
        View::composer('home', HomeReviewsComposer::class);
    }

    /**
     * Outside production, N+1 queries and attributes missing from $fillable throw instead of failing silently.
     */
    private function configureModels(): void
    {
        $isStrict = ! $this->app->isProduction();

        Model::preventLazyLoading($isStrict);
        Model::preventSilentlyDiscardingAttributes($isStrict);
    }
}
