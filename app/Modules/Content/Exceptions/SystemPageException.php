<?php

namespace App\Modules\Content\Exceptions;

use RuntimeException;

final class SystemPageException extends RuntimeException
{
    public static function cannotDelete(): self
    {
        return new self(__('This page is part of the store and cannot be deleted. Unpublish it instead.'));
    }
}
