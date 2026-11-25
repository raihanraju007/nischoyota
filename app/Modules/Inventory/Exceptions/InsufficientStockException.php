<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly int $availableStock)
    {
        parent::__construct(__('Only :count in stock.', ['count' => $availableStock]));
    }
}
