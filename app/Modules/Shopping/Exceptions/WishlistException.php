<?php

namespace App\Modules\Shopping\Exceptions;

use RuntimeException;

/**
 * Why a wishlist change was refused; the message is shown to the customer.
 */
final class WishlistException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self(__('This product is not available.'));
    }

    public static function chooseOptions(): self
    {
        return new self(__('Please choose an option on the product page.'));
    }
}
