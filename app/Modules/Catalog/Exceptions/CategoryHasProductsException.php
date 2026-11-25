<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class CategoryHasProductsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('Move or delete its products first.'));
    }
}
