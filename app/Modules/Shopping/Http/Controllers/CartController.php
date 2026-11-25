<?php

namespace App\Modules\Shopping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductListingService;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The cart page (F-021).
 */
final class CartController extends Controller
{
    private const POPULAR_PRODUCTS = 4;

    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductListingService $listingService,
    ) {}

    public function index(Request $request, CurrentCart $currentCart): View
    {
        $contents = $currentCart->contents();

        if ($contents->isEmpty()) {
            return view('cart.empty', ['popularCards' => $this->listingService->bestSellerCards(self::POPULAR_PRODUCTS)]);
        }

        return view('cart.index', [
            'contents' => $contents,
            'totals' => $this->cartService->totalsFor($contents, $request->user()),
        ]);
    }

    public function clear(CurrentCart $currentCart): RedirectResponse
    {
        $this->cartService->clear($currentCart->owner());

        return to_route('cart')->with('info', __('Cart cleared.'));
    }
}
