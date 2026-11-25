<?php

namespace App\Modules\Catalog\Data;

use App\Modules\Catalog\Enums\ProductListTab;

final readonly class ProductFilter
{
    public function __construct(
        public ProductListTab $tab,
        public ?string $search,
        public ?int $categoryId,
    ) {}
}
