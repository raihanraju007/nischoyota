<?php

namespace App\Modules\Shopping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Http\Requests\ApplyCouponRequest;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Http\RedirectResponse;

/**
 * Coupon preview on the cart page (F-021, rule 8).
 */
final class CartCouponController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(ApplyCouponRequest $request, CurrentCart $currentCart): RedirectResponse
    {
        try {
            $discount = $this->cartService->applyCoupon($currentCart->ownerOrFail(), $request->code());
        } catch (CouponNotApplicableException $exception) {
            return to_route('cart')->withErrors(['code' => $exception->getMessage()])->withInput();
        }

        return to_route('cart')->with('success', __('Coupon :code applied.', ['code' => $discount->code]));
    }

    public function destroy(CurrentCart $currentCart): RedirectResponse
    {
        $this->cartService->removeCoupon($currentCart->ownerOrFail());

        return to_route('cart')->with('info', __('Coupon removed.'));
    }
}
