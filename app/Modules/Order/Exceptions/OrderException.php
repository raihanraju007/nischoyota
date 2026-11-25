<?php

namespace App\Modules\Order\Exceptions;

use App\Modules\Order\Enums\OrderStatus;
use RuntimeException;

/**
 * Why a change to a placed order was refused; the message is shown to the customer or staff.
 */
final class OrderException extends RuntimeException
{
    public static function alreadyCancelled(): self
    {
        return new self(__('This order is already cancelled.'));
    }

    public static function notCancellable(): self
    {
        return new self(__('This order is already being prepared and can no longer be cancelled. Please call us.'));
    }

    public static function invalidTransition(OrderStatus $fromStatus, OrderStatus $toStatus): self
    {
        return new self(__('An order cannot move from :from to :to.', ['from' => $fromStatus->label(), 'to' => $toStatus->label()]));
    }

    public static function courierRequired(): self
    {
        return new self(__('Enter the courier name before marking the order as shipped.'));
    }

    public static function notPayable(): self
    {
        return new self(__('This order has no pending payment to mark as paid.'));
    }
}
