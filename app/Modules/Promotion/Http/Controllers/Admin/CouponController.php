<?php

namespace App\Modules\Promotion\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Http\Requests\Admin\SaveCouponRequest;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function index(): View
    {
        return view('admin.coupons.index', [
            'coupons' => $this->couponService->paginateForAdmin(),
            'now' => now(),
        ]);
    }

    public function create(): View
    {
        return $this->form(coupon: null);
    }

    public function edit(Coupon $coupon): View
    {
        return $this->form($coupon);
    }

    public function store(SaveCouponRequest $request): RedirectResponse
    {
        $coupon = $this->couponService->create($request->toData());

        return to_route('admin.coupons.index')->with('success', __('Coupon :code created.', ['code' => $coupon->code]));
    }

    public function update(SaveCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->couponService->update($coupon, $request->toData());

        return to_route('admin.coupons.index')->with('success', __('Coupon :code updated.', ['code' => $coupon->code]));
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->couponService->delete($coupon);

        return to_route('admin.coupons.index')->with('success', __('Coupon :code deleted.', ['code' => $coupon->code]));
    }

    private function form(?Coupon $coupon): View
    {
        return view('admin.coupons.form', [
            'coupon' => $coupon,
            'typeOptions' => collect(CouponType::cases())->mapWithKeys(fn (CouponType $type): array => [$type->value => $type->label()])->all(),
            'timezone' => config('shop.timezone'),
        ]);
    }
}
