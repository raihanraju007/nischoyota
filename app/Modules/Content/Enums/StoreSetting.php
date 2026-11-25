<?php

namespace App\Modules\Content\Enums;

/**
 * Keys of the settings table (ADR-011) and the value used until the admin saves one.
 */
enum StoreSetting: string
{
    case StoreName = 'store_name';
    case SupportPhone = 'support_phone';
    case SupportEmail = 'support_email';
    case StoreAddress = 'store_address';
    case SupportHours = 'support_hours';
    case FreeDeliveryThreshold = 'free_delivery_threshold';
    case LowStockThreshold = 'low_stock_threshold';

    public function defaultValue(): string|int|null
    {
        return match ($this) {
            self::StoreName => 'Nischoyota',
            self::SupportPhone => '+880 1626-045102',
            self::SupportEmail => 'info@nischoyota.com',
            self::StoreAddress => 'Uttara, Dhaka, Bangladesh',
            self::SupportHours => 'Everyday, 9:00 AM – 10:00 PM',
            self::FreeDeliveryThreshold => (int) config('shop.free_delivery_threshold'),
            self::LowStockThreshold => 5,
        };
    }
}
