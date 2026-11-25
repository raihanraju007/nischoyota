<?php

namespace App\Modules\Shopping\Data;

use App\Modules\Location\Data\DeliveryQuote;

/**
 * The cart page's order summary. Money in paisa. A null delivery means "calculated at checkout".
 */
final readonly class CartTotals
{
    public function __construct(
        public int $subtotal,
        public int $discount,
        public ?string $couponCode,
        public ?string $couponError,
        public ?DeliveryQuote $delivery,
        public ?int $amountToFreeDelivery,
    ) {}

    public function deliveryCharge(): int
    {
        return $this->delivery?->charge ?? 0;
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount + $this->deliveryCharge();
    }
}
