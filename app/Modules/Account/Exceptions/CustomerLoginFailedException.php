<?php

namespace App\Modules\Account\Exceptions;

use RuntimeException;

final class CustomerLoginFailedException extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self(__('These credentials do not match our records.'));
    }

    public static function accountBlocked(): self
    {
        return new self(__('This account is blocked. Please contact customer support.'));
    }
}
