<?php

namespace App\Modules\Catalog\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Archived => __('Archived'),
        };
    }

    /**
     * Only active products are shown in the storefront.
     */
    public function isVisibleInStore(): bool
    {
        return $this === self::Active;
    }
}
