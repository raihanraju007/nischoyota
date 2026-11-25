<?php

namespace App\Modules\Account\Exceptions;

use RuntimeException;

final class AddressLimitReachedException extends RuntimeException
{
    public function __construct(int $maximum)
    {
        parent::__construct(__('You can save up to :max addresses. Delete one to add another.', ['max' => $maximum]));
    }
}
