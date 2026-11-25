<?php

namespace App\Modules\Order\Data;

/**
 * One customer's order numbers for the admin (F-026). Money in paisa.
 */
final readonly class CustomerOrderStats
{
    public function __construct(
        public int $orderCount = 0,
        public int $deliveredTotal = 0,
        public int $cancelledCount = 0,
    ) {}
}
