<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class InvalidCategoryParentException extends RuntimeException
{
    public static function categoryHasSubcategories(): self
    {
        return new self(__('A category with subcategories must stay at the top level.'));
    }

    public static function cannotBeItsOwnParent(): self
    {
        return new self(__('A category cannot be its own parent.'));
    }
}
