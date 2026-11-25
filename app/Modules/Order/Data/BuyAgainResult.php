<?php

namespace App\Modules\Order\Data;

/**
 * What "Buy again" put in the cart, and the items it could not add.
 */
final readonly class BuyAgainResult
{
    /**
     * @param  list<string>  $skippedProductNames
     */
    public function __construct(
        public int $addedCount,
        public array $skippedProductNames,
    ) {}
}
