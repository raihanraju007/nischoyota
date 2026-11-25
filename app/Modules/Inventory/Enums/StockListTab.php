<?php

namespace App\Modules\Inventory\Enums;

enum StockListTab: string
{
    case All = 'all';
    case LowStock = 'low';
    case OutOfStock = 'out';

    public function label(): string
    {
        return match ($this) {
            self::All => __('All'),
            self::LowStock => __('Low stock'),
            self::OutOfStock => __('Out of stock'),
        };
    }
}
