<?php

namespace App\Modules\Catalog\Data;

/**
 * One row of the variants table. Money values are paisa; cost price only applies when $shouldUpdateCostPrice.
 * Stock is not here: it changes only through Inventory (ADR-013).
 */
final readonly class VariantRowData
{
    public function __construct(
        public int $variantId,
        public ?string $sku,
        public int $price,
        public ?int $comparePrice,
        public ?int $costPrice,
        public bool $shouldUpdateCostPrice,
        public bool $isActive,
        public ?int $productImageId,
    ) {}
}
