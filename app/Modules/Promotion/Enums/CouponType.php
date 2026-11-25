<?php

namespace App\Modules\Promotion\Enums;

enum CouponType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => __('Fixed amount (৳)'),
            self::Percent => __('Percentage (%)'),
        };
    }
}
