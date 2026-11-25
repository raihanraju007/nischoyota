<?php

namespace App\Modules\Order\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Paid => __('Paid'),
            self::Refunded => __('Refunded'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
