<?php

namespace App\Modules\Order\Enums;

/**
 * Phase 1 is Cash on Delivery only; online payment needs an ADR first.
 */
enum PaymentMethod: string
{
    case CashOnDelivery = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => __('Cash on Delivery'),
        };
    }
}
