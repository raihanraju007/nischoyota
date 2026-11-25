<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class AttributeInUseException extends RuntimeException
{
    public static function value(): self
    {
        return new self(__('This value is used by product variants. Remove it from those products first.'));
    }

    public static function attribute(): self
    {
        return new self(__('Values of this option are used by product variants. Remove them from those products first.'));
    }
}
