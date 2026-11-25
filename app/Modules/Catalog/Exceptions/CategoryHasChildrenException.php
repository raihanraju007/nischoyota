<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class CategoryHasChildrenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('Delete or move its subcategories first.'));
    }
}
