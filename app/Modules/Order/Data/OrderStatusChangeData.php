<?php

namespace App\Modules\Order\Data;

use App\Modules\Order\Enums\OrderStatus;

/**
 * A staff status change (F-025). Courier name / tracking code are saved when given.
 */
final readonly class OrderStatusChangeData
{
    public function __construct(
        public OrderStatus $toStatus,
        public ?string $note,
        public ?string $courierName,
        public ?string $trackingCode,
        public int $userId,
    ) {}
}
