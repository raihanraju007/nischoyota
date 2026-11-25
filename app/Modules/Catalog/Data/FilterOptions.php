<?php

namespace App\Modules\Catalog\Data;

/**
 * Choices shown in the filter sidebar, taken from the listed products.
 */
final readonly class FilterOptions
{
    /**
     * @param  list<array{slug: string, name: string, count: int}>  $categories
     * @param  list<array{slug: string, name: string}>  $brands
     * @param  list<array{id: int, value: string, hex: ?string}>  $colors
     * @param  list<array{id: int, value: string}>  $sizes
     */
    public function __construct(
        public array $categories,
        public array $brands,
        public array $colors,
        public array $sizes,
    ) {}
}
