<?php

namespace App\Modules\Inventory\Data;

use App\Modules\Inventory\Enums\StockListTab;

final readonly class StockFilter
{
    public function __construct(
        public StockListTab $tab,
        public ?string $search,
    ) {}
}
