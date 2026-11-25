<?php

namespace App\Modules\Shopping\Exceptions;

use RuntimeException;

/**
 * Why a cart change was refused; the message is shown to the customer.
 */
final class CartException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self(__('This product is not available.'));
    }

    public static function outOfStock(): self
    {
        return new self(__('This item is out of stock.'));
    }

    public static function notEnoughStock(int $stockQuantity): self
    {
        return new self(__('Only :count left in stock.', ['count' => $stockQuantity]));
    }

    public static function quantityLimit(int $maxQuantity): self
    {
        return new self(__('You can buy at most :max of one item.', ['max' => $maxQuantity]));
    }
}
