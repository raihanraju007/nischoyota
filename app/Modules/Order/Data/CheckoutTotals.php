<?php

namespace App\Modules\Order\Data;

use App\Modules\Location\Data\DeliveryQuote;
use App\Modules\Promotion\Data\CouponDiscount;

/**
 * Amounts for the checkout summary and the order (paisa). A null delivery means the area is not
 * chosen yet or not delivered to.
 */
final readonly class CheckoutTotals
{
    public function __construct(
        public int $subtotal,
        public ?string $couponCode,
        public ?CouponDiscount $coupon,
        public ?string $couponError,
        public ?DeliveryQuote $delivery,
    ) {}

    public function discount(): int
    {
        return $this->coupon?->discountAmount ?? 0;
    }

    public function deliveryCharge(): int
    {
        return $this->delivery?->charge ?? 0;
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount() + $this->deliveryCharge();
    }
}
