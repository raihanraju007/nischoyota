<?php

namespace App\Modules\Order\Exceptions;

use RuntimeException;

/**
 * Why an order could not be placed; the message is shown to the customer and nothing is saved.
 */
final class CheckoutException extends RuntimeException
{
    public static function accountBlocked(): self
    {
        return new self(__("We can't take orders from this account. Please contact customer support."));
    }

    public static function cartNotReady(): self
    {
        return new self(__('Please check your cart: some items need your attention.'));
    }

    public static function addressNotFound(): self
    {
        return new self(__('Please choose a delivery address.'));
    }

    public static function noDelivery(): self
    {
        return new self(__("We don't deliver to this area yet."));
    }

    public static function pricesChanged(): self
    {
        return new self(__('Prices changed — please check your order and place it again.'));
    }

    public static function coupon(string $reason): self
    {
        return new self($reason);
    }

    public static function soldOut(string $productName, int $availableStock): self
    {
        return new self(__('Sorry, :product just sold out. Only :count left.', ['product' => $productName, 'count' => $availableStock]));
    }
}
