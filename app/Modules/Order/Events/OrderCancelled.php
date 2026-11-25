<?php

namespace App\Modules\Order\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An order was cancelled and committed (notifications, F-029).
 */
final class OrderCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int $orderId) {}
}
