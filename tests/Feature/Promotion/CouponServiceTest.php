<?php

use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Services\CouponService;

beforeEach(function () {
    $this->service = app(CouponService::class);
});

function couponMessage(callable $apply): ?string
{
    try {
        $apply();
    } catch (CouponNotApplicableException $exception) {
        return $exception->getMessage();
    }

    return null;
}

describe('discount', function () {
    it('takes a fixed amount off, never more than the subtotal', function (int $subtotal, int $expected) {
        $coupon = Coupon::factory()->make(['value' => 12000]);

        expect($this->service->discountFor($coupon, $subtotal))->toBe($expected);
    })->with([
        'normal' => [150000, 12000],
        'subtotal smaller than value' => [5000, 5000],
    ]);

    it('takes a percentage off, rounded down and capped', function (int $percent, ?int $max, int $subtotal, int $expected) {
        $coupon = Coupon::factory()->percent($percent, $max)->make();

        expect($this->service->discountFor($coupon, $subtotal))->toBe($expected);
    })->with([
        '10% of ৳1,999.99' => [10, null, 199999, 19999],
        'capped at ৳500' => [10, 50000, 800000, 50000],
        'under the cap' => [10, 50000, 300000, 30000],
        '100% is the subtotal' => [100, null, 45000, 45000],
    ]);
});

describe('apply', function () {
    it('applies a valid code in any case', function () {
        $coupon = Coupon::factory()->create(['code' => 'SAVE120', 'value' => 12000]);

        $discount = $this->service->apply(' save120 ', 150000);

        expect($discount->couponId)->toBe($coupon->id)
            ->and($discount->code)->toBe('SAVE120')
            ->and($discount->discountAmount)->toBe(12000);
    });

    it('explains why a code does not apply', function (array $coupon, int $subtotal, int $customerUses, string $message) {
        Coupon::factory()->create(['code' => 'TEST', ...$coupon]);

        expect(couponMessage(fn () => $this->service->apply('TEST', $subtotal, $customerUses)))->toBe($message);
    })->with([
        'inactive' => [['is_active' => false], 100000, 0, 'This coupon code is not valid.'],
        'not started' => [['starts_at' => now()->addDay()], 100000, 0, 'This coupon is not active yet.'],
        'expired' => [['expires_at' => now()->subMinute()], 100000, 0, 'This coupon has expired.'],
        'below minimum' => [['min_order_amount' => 150000], 149999, 0, 'Spend at least ৳ 1,500 to use this coupon.'],
        'used up' => [['usage_limit' => 2, 'used_count' => 2], 100000, 0, 'This coupon has been fully used.'],
        'used by customer' => [['usage_limit_per_user' => 1], 100000, 1, 'You have already used this coupon.'],
    ]);

    it('rejects an unknown code', function () {
        expect(couponMessage(fn () => $this->service->apply('NOPE', 100000)))->toBe('This coupon code is not valid.');
    });

    it('accepts the exact minimum and the last allowed use', function () {
        Coupon::factory()->create(['code' => 'EDGE', 'min_order_amount' => 150000, 'usage_limit' => 3, 'used_count' => 2, 'usage_limit_per_user' => 2]);

        expect($this->service->apply('EDGE', 150000, customerUsageCount: 1)->discountAmount)->toBe(12000);
    });
});
