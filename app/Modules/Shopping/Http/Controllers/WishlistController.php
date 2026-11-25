<?php

namespace App\Modules\Shopping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopping\Exceptions\CartException;
use App\Modules\Shopping\Exceptions\WishlistException;
use App\Modules\Shopping\Http\CurrentWishlist;
use App\Modules\Shopping\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in customer's wishlist (F-022). The heart answers JSON for the storefront script.
 */
final class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService) {}

    public function index(CurrentWishlist $currentWishlist): View
    {
        $cards = $this->wishlistService->cardsFor($currentWishlist->productIds());

        return $cards === [] ? view('wishlist.empty') : view('wishlist.index', ['cards' => $cards]);
    }

    public function toggle(Request $request, CurrentWishlist $currentWishlist, int $product): JsonResponse|RedirectResponse
    {
        try {
            $isSaved = $this->wishlistService->toggle($request->user(), $product);
        } catch (WishlistException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->with('error', $exception->getMessage());
        }

        $currentWishlist->forget();
        $message = $isSaved ? __('Added to wishlist.') : __('Removed from wishlist.');

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'isSaved' => $isSaved, 'count' => $currentWishlist->count()])
            : back()->with($isSaved ? 'success' : 'info', $message);
    }

    public function moveToCart(Request $request, int $product): RedirectResponse
    {
        try {
            $this->wishlistService->moveToCart($request->user(), $product);
        } catch (WishlistException|CartException $exception) {
            return to_route('wishlist')->with('error', $exception->getMessage());
        }

        return to_route('wishlist')->with('success', __('Moved to cart.'));
    }
}
