<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class BrandHasProductsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('This brand is used by products. Change their brand first.'));
    }
}
