<?php

namespace App\Http\View\Composers;

use App\Modules\Shopping\Http\CurrentCart;
use Illuminate\View\View;

/**
 * The visitor's cart lines for the slide-in mini cart on every storefront page (F-021).
 */
final class CartDrawerComposer
{
    public function __construct(private readonly CurrentCart $currentCart) {}

    public function compose(View $view): void
    {
        $view->with('cartContents', $this->currentCart->contents());
    }
}
