<?php

namespace App\Modules\Catalog\Enums;

enum BulkProductAction: string
{
    case Publish = 'publish';
    case MoveToDraft = 'draft';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Publish => __('Publish'),
            self::MoveToDraft => __('Move to draft'),
            self::Archive => __('Archive'),
        };
    }

    public function targetStatus(): ProductStatus
    {
        return match ($this) {
            self::Publish => ProductStatus::Active,
            self::MoveToDraft => ProductStatus::Draft,
            self::Archive => ProductStatus::Archived,
        };
    }
}
