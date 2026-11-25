<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use Illuminate\Support\Str;

/**
 * NS-{first 2 letters of the department}-{4 digits}, e.g. NS-EL-0035 for a product in Electrical → Fans.
 */
final class ProductSkuGenerator
{
    private const STORE_PREFIX = 'NS';

    private const NUMBER_DIGITS = 4;

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function generateFor(int $categoryId): string
    {
        $prefix = self::STORE_PREFIX.'-'.$this->departmentCode($categoryId).'-';
        $number = $this->productRepository->highestSkuNumber($prefix) + 1;

        while ($this->productRepository->skuExists($sku = $prefix.str_pad((string) $number, self::NUMBER_DIGITS, '0', STR_PAD_LEFT))) {
            $number++;
        }

        return $sku;
    }

    private function departmentCode(int $categoryId): string
    {
        $departmentName = $this->categoryRepository->departmentNameOf($categoryId);
        $letters = Str::upper(substr(Str::slug($departmentName, ''), 0, 2));

        return strlen($letters) === 2 ? $letters : 'XX';
    }
}
