<?php

namespace App\Modules\Inventory\Data;

use App\Modules\Inventory\Enums\StockMovementReason;

final readonly class StockAdjustmentData
{
    /**
     * @param  int  $quantity  Amount to add/remove, or the counted total for a stock count
     */
    public function __construct(
        public StockMovementReason $reason,
        public int $quantity,
        public ?string $note,
        public ?int $userId,
    ) {}
}
