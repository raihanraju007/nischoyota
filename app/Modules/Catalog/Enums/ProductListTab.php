<?php

namespace App\Modules\Catalog\Enums;

/**
 * Tabs on the admin product list.
 */
enum ProductListTab: string
{
    case All = 'all';
    case Active = 'active';
    case Draft = 'draft';
    case Archived = 'archived';
    case OutOfStock = 'out-of-stock';

    public function label(): string
    {
        return match ($this) {
            self::All => __('All'),
            self::Active => __('Active'),
            self::Draft => __('Draft'),
            self::Archived => __('Archived'),
            self::OutOfStock => __('Out of stock'),
        };
    }

    public function status(): ?ProductStatus
    {
        return match ($this) {
            self::Active => ProductStatus::Active,
            self::Draft => ProductStatus::Draft,
            self::Archived => ProductStatus::Archived,
            self::All, self::OutOfStock => null,
        };
    }
}
