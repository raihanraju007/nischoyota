<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class TooManyProductImagesException extends RuntimeException
{
    public function __construct(int $maximum)
    {
        parent::__construct(__('A product can have at most :max images. Remove some first.', ['max' => $maximum]));
    }
}
