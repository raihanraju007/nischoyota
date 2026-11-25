<?php

namespace App\Http\View\Composers;

use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Content\Services\SettingService;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Http\CurrentWishlist;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Data for the storefront shell (header, footer, mobile menu) on every page.
 */
final class StorefrontShellComposer
{
    private const DESKTOP_NAV_DEPARTMENTS = 5;

    private const FOOTER_DEPARTMENTS = 5;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly SettingService $settingService,
        private readonly Guard $auth,
        private readonly CurrentCart $currentCart,
        private readonly CurrentWishlist $currentWishlist,
    ) {}

    public function compose(View $view): void
    {
        $menu = $this->categoryService->menuTree();
        $user = $this->auth->user();

        $view->with([
            'menuDepartments' => $menu,
            'navDepartments' => array_slice($menu, 0, self::DESKTOP_NAV_DEPARTMENTS),
            'footerDepartments' => array_slice($menu, 0, self::FOOTER_DEPARTMENTS),
            'storeSettings' => $this->settingService->current(),
            'signedInFirstName' => $user === null ? null : Str::before($user->name, ' '),
            'cartCount' => $this->currentCart->contents()->lineCount(),
            'wishlistCount' => $this->currentWishlist->count(),
        ]);
    }
}
