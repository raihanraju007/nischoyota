<?php

namespace App\Modules\Catalog\Data;

/**
 * Money values are paisa. Cost price is only written when $shouldUpdateCostPrice (admins, ADR-012).
 * $openingStock is set only when creating; afterwards stock changes go through Inventory (ADR-013).
 */
final readonly class DefaultVariantData
{
    public function __construct(
        public ?string $sku,
        public int $price,
        public ?int $comparePrice,
        public ?int $costPrice,
        public bool $shouldUpdateCostPrice,
        public ?int $openingStock,
        public ?int $weightGrams,
    ) {}
}
