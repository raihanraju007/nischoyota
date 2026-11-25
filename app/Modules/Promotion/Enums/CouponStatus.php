<?php

namespace App\Modules\Promotion\Enums;

/**
 * Derived from a coupon's dates, usage and active flag (not stored).
 */
enum CouponStatus: string
{
    case Active = 'active';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case UsedUp = 'used up';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Scheduled => __('Scheduled'),
            self::Expired => __('Expired'),
            self::UsedUp => __('Used up'),
            self::Inactive => __('Inactive'),
        };
    }
}
