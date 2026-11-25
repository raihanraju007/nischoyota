<?php

namespace App\Modules\Account\Exceptions;

use App\Modules\Account\Models\User;
use RuntimeException;

/**
 * Correct password but the phone was never verified: the customer must enter a code first.
 */
final class PhoneNotVerifiedException extends RuntimeException
{
    public function __construct(public readonly User $user)
    {
        parent::__construct(__('Please verify your phone number first. We sent you a code.'));
    }
}
