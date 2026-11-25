<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Data\ProductFilter;
use App\Modules\Catalog\Enums\BulkProductAction;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Exceptions\TooManyProductImagesException;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\PublicImageStorage;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public const MAX_IMAGES = 8;

    private const IMAGE_DIRECTORY = 'products';

    private const FALLBACK_SLUG = 'product';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductSkuGenerator $skuGenerator,
        private readonly PublicImageStorage $imageStorage,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->productRepository->paginateForAdmin($filter);
    }

    /**
     * @return array<string, int>
     */
    public function countPerTab(ProductFilter $filter): array
    {
        return $this->productRepository->countPerTab($filter->search, $filter->categoryId);
    }

    public function loadForEditing(Product $product): Product
    {
        return $this->productRepository->loadForEditing($product);
    }

    /**
     * @throws TooManyProductImagesException
     */
    public function create(ProductData $data): Product
    {
        $this->ensureImageLimit(currentCount: 0, data: $data);

        return DB::transaction(function () use ($data): Product {
            $product = $this->productRepository->create([
                ...$this->productAttributesFrom($data),
                'slug' => $data->slug ?? $this->uniqueSlugFor($data->name),
                'published_at' => $data->status === ProductStatus::Active ? now() : null,
            ]);

            $variant = $this->saveDefaultVariant($product, $data);
            $this->inventoryService->recordOpeningStock($variant->id, (int) $data->variant?->openingStock, $data->actingUserId);
            $this->addImages($product, $data, startPosition: 0);

            return $product;
        });
    }

    /**
     * @throws TooManyProductImagesException
     */
    public function update(Product $product, ProductData $data): Product
    {
        $this->ensureImageLimit($this->productRepository->imageCount($product) - count($data->removedImageIds), $data);

        $deletedImagePaths = DB::transaction(function () use ($product, $data) {
            $this->productRepository->update($product, [
                ...$this->productAttributesFrom($data),
                'slug' => $data->slug ?? ($product->name === $data->name ? $product->slug : $this->uniqueSlugFor($data->name)),
                'published_at' => $product->published_at ?? ($data->status === ProductStatus::Active ? now() : null),
            ]);

            $this->saveDefaultVariant($product, $data);
            $deletedPaths = $this->productRepository->deleteImages($product, $data->removedImageIds);
            $this->addImages($product, $data, startPosition: $this->productRepository->imageCount($product));
            $this->productRepository->reorderImages($product, $data->coverImageId);

            return $deletedPaths;
        });

        $deletedImagePaths->each($this->imageStorage->delete(...));

        return $product;
    }

    /**
     * Average and count of approved reviews, kept on the product for cards, filters and sorting (F-027).
     */
    public function updateRatingCache(int $productId, float $ratingAverage, int $ratingCount): void
    {
        $this->productRepository->updateRatingCache($productId, round($ratingAverage, 2), $ratingCount);
    }

    public function delete(Product $product): void
    {
        $imagePaths = $this->productRepository->imagePaths($product);

        $this->productRepository->delete($product);

        $imagePaths->each($this->imageStorage->delete(...));
    }

    /**
     * @param  list<int>  $productIds
     */
    public function applyBulkAction(BulkProductAction $action, array $productIds): int
    {
        return $this->productRepository->updateStatus($productIds, $action->targetStatus());
    }

    /**
     * @return array<string, mixed>
     */
    private function productAttributesFrom(ProductData $data): array
    {
        return [
            'category_id' => $data->categoryId,
            'brand_id' => $data->brandId,
            'name' => $data->name,
            'short_description' => $data->shortDescription,
            'description' => $data->description,
            'specifications' => $data->specifications === [] ? null : $data->specifications,
            'sell_unit' => $data->sellUnit,
            'unit_quantity' => $data->unitQuantity,
            'status' => $data->status,
            'is_featured' => $data->isFeatured,
            'allow_preorder' => $data->allowsPreorder,
            'meta_title' => $data->metaTitle,
            'meta_description' => $data->metaDescription,
            ...$this->cachedPriceAttributesFrom($data),
        ];
    }

    /**
     * Only for simple products: a product with options gets its cached prices from ProductVariantService.
     *
     * @return array<string, int>
     */
    private function cachedPriceAttributesFrom(ProductData $data): array
    {
        if ($data->variant === null) {
            return [];
        }

        return [
            'min_price' => $data->variant->price,
            'max_discount_percent' => ProductVariant::discountPercentFor($data->variant->price, $data->variant->comparePrice),
        ];
    }

    /**
     * Simple products only; variants of a product with options are saved on the Variants page.
     */
    private function saveDefaultVariant(Product $product, ProductData $data): ?ProductVariant
    {
        $variant = $data->variant;

        if ($variant === null) {
            return null;
        }

        $currentSku = $product->defaultVariant?->sku;

        $attributes = [
            'sku' => $variant->sku ?? $currentSku ?? $this->skuGenerator->generateFor($data->categoryId),
            'price' => $variant->price,
            'compare_price' => $variant->comparePrice,
            'weight_grams' => $variant->weightGrams,
            'is_active' => true,
        ];

        if ($variant->shouldUpdateCostPrice) {
            $attributes['cost_price'] = $variant->costPrice;
        }

        return $this->productRepository->saveDefaultVariant($product, $attributes);
    }

    private function addImages(Product $product, ProductData $data, int $startPosition): void
    {
        foreach ($data->newImages as $offset => $image) {
            $path = $this->imageStorage->store($image, self::IMAGE_DIRECTORY);
            $this->productRepository->addImage($product, $path, $startPosition + $offset);
        }
    }

    private function ensureImageLimit(int $currentCount, ProductData $data): void
    {
        if ($currentCount + count($data->newImages) > self::MAX_IMAGES) {
            throw new TooManyProductImagesException(self::MAX_IMAGES);
        }
    }

    private function uniqueSlugFor(string $name): string
    {
        return UniqueSlug::generate($name, $this->productRepository->slugExists(...), self::FALLBACK_SLUG);
    }
}
