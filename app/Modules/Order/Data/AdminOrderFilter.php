<?php

namespace App\Modules\Order\Data;

use App\Modules\Order\Enums\OrderDateRange;
use App\Modules\Order\Enums\OrderStatus;

/**
 * Admin order list filter. A null status is the "All" tab.
 */
final readonly class AdminOrderFilter
{
    public function __construct(
        public ?OrderStatus $status,
        public ?string $search,
        public OrderDateRange $dateRange,
    ) {}

    /**
     * @return array<string, string>
     */
    public function queryFor(?OrderStatus $status): array
    {
        return array_filter([
            'status' => $status?->value,
            'search' => $this->search,
            'date' => $this->dateRange === OrderDateRange::AllTime ? null : $this->dateRange->value,
        ]);
    }
}
