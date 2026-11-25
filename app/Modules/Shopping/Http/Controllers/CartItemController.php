<?php

namespace App\Modules\Shopping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shopping\Exceptions\CartException;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Http\Requests\AddToCartRequest;
use App\Modules\Shopping\Http\Requests\UpdateCartItemRequest;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;

/**
 * Cart lines (F-021). Adding answers JSON for the storefront script and redirects otherwise.
 */
final class CartItemController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(AddToCartRequest $request, CurrentCart $currentCart): JsonResponse|RedirectResponse
    {
        try {
            $this->cartService->add($currentCart->ownerForChange(), $request->variantId(), $request->quantity());
        } catch (CartException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->with('error', $exception->getMessage());
        }

        return match (true) {
            $request->isBuyNow() => to_route('checkout'),
            $request->expectsJson() => $this->addedResponse($currentCart),
            default => back()->with('success', __('Added to cart.')),
        };
    }

    public function update(UpdateCartItemRequest $request, CurrentCart $currentCart, int $item): RedirectResponse
    {
        try {
            $this->cartService->updateQuantity($currentCart->ownerOrFail(), $item, $request->quantity());
        } catch (CartException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Quantity updated.'));
    }

    public function destroy(CurrentCart $currentCart, int $item): RedirectResponse
    {
        $this->cartService->remove($currentCart->ownerOrFail(), $item);

        return back()->with('info', __('Removed from cart.'));
    }

    /**
     * New badge count and the re-rendered cart drawer, so the page does not reload.
     */
    private function addedResponse(CurrentCart $currentCart): JsonResponse
    {
        return response()->json([
            'message' => __('Added to cart.'),
            'count' => $currentCart->contents()->lineCount(),
            'drawer' => Blade::render('<x-layout.cart-drawer />'),
        ]);
    }
}
