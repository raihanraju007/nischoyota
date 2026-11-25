<?php

namespace App\Modules\Promotion\Data;

/**
 * A coupon that applies to an order, and how much it takes off (paisa).
 */
final readonly class CouponDiscount
{
    public function __construct(
        public int $couponId,
        public string $code,
        public int $discountAmount,
    ) {}
}
