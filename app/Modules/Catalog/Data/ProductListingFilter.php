<?php

namespace App\Modules\Catalog\Data;

use App\Modules\Catalog\Enums\ProductSort;

/**
 * Storefront listing filters (F-016). Prices in paisa; categories/brands by slug; colour/size by attribute value id.
 */
final readonly class ProductListingFilter
{
    /**
     * @param  list<string>  $categorySlugs
     * @param  list<string>  $brandSlugs
     * @param  list<int>  $colorValueIds
     * @param  list<int>  $sizeValueIds
     */
    public function __construct(
        public array $categorySlugs = [],
        public array $brandSlugs = [],
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?int $minRating = null,
        public ?int $minDiscount = null,
        public bool $onlyInStock = false,
        public bool $onlyPreorder = false,
        public array $colorValueIds = [],
        public array $sizeValueIds = [],
        public ProductSort $sort = ProductSort::Popular,
    ) {}

    public function hasActiveFilters(): bool
    {
        return $this->categorySlugs !== [] || $this->brandSlugs !== [] || $this->priceMin !== null || $this->priceMax !== null
            || $this->minRating !== null || $this->minDiscount !== null || $this->onlyInStock || $this->onlyPreorder
            || $this->colorValueIds !== [] || $this->sizeValueIds !== [];
    }
}
