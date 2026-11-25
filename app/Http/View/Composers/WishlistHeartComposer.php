<?php

namespace App\Http\View\Composers;

use App\Modules\Shopping\Http\CurrentWishlist;
use Illuminate\View\View;

/**
 * Saved product ids for the wishlist hearts on product cards and the product page (F-022).
 */
final class WishlistHeartComposer
{
    public function __construct(private readonly CurrentWishlist $currentWishlist) {}

    public function compose(View $view): void
    {
        $view->with('wishlistedProductIds', $this->currentWishlist->productIds());
    }
}
