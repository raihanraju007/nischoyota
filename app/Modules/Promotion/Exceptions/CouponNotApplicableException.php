<?php

namespace App\Modules\Promotion\Exceptions;

use App\Support\Money;
use RuntimeException;

/**
 * Why a coupon code cannot be used; the message is shown to the customer.
 */
final class CouponNotApplicableException extends RuntimeException
{
    public static function invalidCode(): self
    {
        return new self(__('This coupon code is not valid.'));
    }

    public static function notStarted(): self
    {
        return new self(__('This coupon is not active yet.'));
    }

    public static function expired(): self
    {
        return new self(__('This coupon has expired.'));
    }

    public static function belowMinimum(int $minimumAmount): self
    {
        return new self(__('Spend at least :amount to use this coupon.', ['amount' => Money::format($minimumAmount)]));
    }

    public static function usedUp(): self
    {
        return new self(__('This coupon has been fully used.'));
    }

    public static function alreadyUsedByCustomer(): self
    {
        return new self(__('You have already used this coupon.'));
    }
}
