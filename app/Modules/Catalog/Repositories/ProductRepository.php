<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Data\ProductFilter;
use App\Modules\Catalog\Enums\ProductListTab;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Products with their variants and images (one aggregate).
 */
final class ProductRepository
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->adminQuery($filter->search, $filter->categoryId)
            ->tap(fn (Builder $query) => $this->applyTab($query, $filter->tab))
            ->with(['category', 'defaultVariant', 'coverImage'])
            ->withCount('variants')
            ->withSum(['variants as active_stock' => fn (Builder $variants) => $variants->where('is_active', true)], 'stock_quantity')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Number of products per tab for the current search and category filter.
     *
     * @return array<string, int> tab value => count
     */
    public function countPerTab(?string $search, ?int $categoryId): array
    {
        return collect(ProductListTab::cases())
            ->mapWithKeys(fn (ProductListTab $tab): array => [
                $tab->value => $this->adminQuery($search, $categoryId)
                    ->tap(fn (Builder $query) => $this->applyTab($query, $tab))
                    ->count(),
            ])
            ->all();
    }

    public function loadForEditing(Product $product): Product
    {
        return $product->load(['defaultVariant', 'images']);
    }

    public function slugExists(string $slug): bool
    {
        return Product::query()->where('slug', $slug)->exists();
    }

    public function skuExists(string $sku): bool
    {
        return ProductVariant::query()->where('sku', $sku)->exists();
    }

    /**
     * Highest number used after a SKU prefix: "NS-EL-" with NS-EL-0034 and NS-EL-0007 → 34.
     */
    public function highestSkuNumber(string $prefix): int
    {
        return (int) ProductVariant::query()
            ->whereLike('sku', $prefix.'%')
            ->pluck('sku')
            ->map(fn (string $sku): int => (int) substr($sku, strlen($prefix)))
            ->max();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product;
    }

    public function updateRatingCache(int $productId, float $ratingAverage, int $ratingCount): void
    {
        Product::query()->whereKey($productId)->update(['rating_average' => $ratingAverage, 'rating_count' => $ratingCount]);
    }

    /**
     * Creates the default variant with stock 0, or updates it without touching stock (ADR-013).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveDefaultVariant(Product $product, array $attributes): ProductVariant
    {
        return $product->variants()->updateOrCreate(['is_default' => true], $attributes);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function addImage(Product $product, string $path, int $sortOrder): ProductImage
    {
        return $product->images()->create(['path' => $path, 'sort_order' => $sortOrder]);
    }

    /**
     * @param  list<int>  $imageIds
     * @return Collection<int, string> paths of the deleted images
     */
    public function deleteImages(Product $product, array $imageIds): Collection
    {
        $images = $product->images()->whereKey($imageIds)->get();
        $product->images()->whereKey($images->modelKeys())->delete();

        return $images->pluck('path');
    }

    /**
     * Rewrite sort_order so the cover comes first and the others keep their order.
     */
    public function reorderImages(Product $product, ?int $coverImageId): void
    {
        $images = $product->images()->get();
        $ordered = $images->sortBy(fn (ProductImage $image): int => $image->id === $coverImageId ? 0 : 1)->values();

        foreach ($ordered as $position => $image) {
            $image->update(['sort_order' => $position]);
        }
    }

    public function imageCount(Product $product): int
    {
        return $product->images()->count();
    }

    /**
     * @return Collection<int, string>
     */
    public function imagePaths(Product $product): Collection
    {
        return $product->images()->pluck('path');
    }

    /**
     * Returns how many of the given products exist (MySQL's affected-rows count skips unchanged rows).
     *
     * @param  list<int>  $productIds
     */
    public function updateStatus(array $productIds, ProductStatus $status): int
    {
        $matchedCount = Product::query()->whereKey($productIds)->count();
        Product::query()->whereKey($productIds)->update(['status' => $status]);

        if ($status === ProductStatus::Active) {
            Product::query()->whereKey($productIds)->whereNull('published_at')->update(['published_at' => now()]);
        }

        return $matchedCount;
    }

    public function categoryHasProducts(Category $category): bool
    {
        return Product::query()->where('category_id', $category->id)->exists();
    }

    public function brandHasProducts(int $brandId): bool
    {
        return Product::query()->where('brand_id', $brandId)->exists();
    }

    /**
     * @return Builder<Product>
     */
    private function adminQuery(?string $search, ?int $categoryId): Builder
    {
        return Product::query()
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $term = '%'.$this->escapeLike($search).'%';
                $query->whereLike('name', $term)
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->whereLike('sku', $term));
            }))
            ->when($categoryId, fn (Builder $query) => $query->whereIn('category_id', $this->categoryWithChildrenIds($categoryId)));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyTab(Builder $query, ProductListTab $tab): void
    {
        if ($tab === ProductListTab::OutOfStock) {
            $query->whereDoesntHave('variants', fn (Builder $variant) => $variant->where('is_active', true)->where('stock_quantity', '>', 0));

            return;
        }

        $query->when($tab->status(), fn (Builder $query, ProductStatus $status) => $query->where('status', $status));
    }

    /**
     * A department filter includes its subcategories.
     *
     * @return list<int>
     */
    private function categoryWithChildrenIds(int $categoryId): array
    {
        return Category::query()
            ->whereKey($categoryId)
            ->orWhere('parent_id', $categoryId)
            ->pluck('id')
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
