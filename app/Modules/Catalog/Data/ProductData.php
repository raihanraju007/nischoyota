<?php

namespace App\Modules\Catalog\Data;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\SellUnit;
use Illuminate\Http\UploadedFile;

final readonly class ProductData
{
    /**
     * @param  list<array{label: string, value: string}>  $specifications
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removedImageIds
     * @param  DefaultVariantData|null  $variant  null when the product has options (variants are saved on the Variants page)
     */
    public function __construct(
        public string $name,
        public ?string $slug,
        public int $categoryId,
        public ?int $brandId,
        public ?string $shortDescription,
        public ?string $description,
        public array $specifications,
        public ProductStatus $status,
        public bool $isFeatured,
        public bool $allowsPreorder,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?DefaultVariantData $variant,
        public array $newImages,
        public array $removedImageIds,
        public ?int $coverImageId,
        public ?int $actingUserId,
        public SellUnit $sellUnit = SellUnit::Piece,
        public ?float $unitQuantity = null,
        public ?string $nameBn = null,
        public ?string $shortDescriptionBn = null,
        public ?string $descriptionBn = null,
    ) {}
}
