<?php

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class InvalidVariantSetupException extends RuntimeException
{
    public static function tooManyAttributes(int $maximum): self
    {
        return new self(__('Choose values from at most :max options.', ['max' => $maximum]));
    }

    public static function tooManyCombinations(int $count, int $maximum): self
    {
        return new self(__('These options make :count variants; the maximum is :max.', ['count' => $count, 'max' => $maximum]));
    }

    public static function valueNotInAttribute(): self
    {
        return new self(__('One of the chosen values does not belong to its option.'));
    }

    public static function defaultMustBeActive(): self
    {
        return new self(__('The default variant must be active.'));
    }

    public static function unknownDefault(): self
    {
        return new self(__('Choose a default variant of this product.'));
    }
}
