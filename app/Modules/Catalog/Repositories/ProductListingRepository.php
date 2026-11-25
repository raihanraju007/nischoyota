<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Data\ListingScope;
use App\Modules\Catalog\Data\ProductListingFilter;
use App\Modules\Catalog\Enums\ProductSort;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Storefront product queries (F-016). Listed = active product in an active category.
 */
final class ProductListingRepository
{
    public const PER_PAGE = 24;

    public const COLOR_ATTRIBUTE_SLUG = 'color';

    public const SIZE_ATTRIBUTE_SLUG = 'size';

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(ListingScope $scope, ProductListingFilter $filter): LengthAwarePaginator
    {
        $query = $this->listedProducts($scope)
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filter))
            ->tap($this->withCardData(...));

        $this->applySort($query, $filter->sort, $scope);

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  list<int>  $productIds
     * @return EloquentCollection<int, Product>
     */
    public function cardProductsByIds(array $productIds): EloquentCollection
    {
        return $this->cardProducts(ListingScope::wholeShop())->whereKey($productIds)->get();
    }

    /**
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function listedProductIds(array $productIds): array
    {
        return $this->listedProducts(ListingScope::wholeShop())->whereKey($productIds)->pluck('id')->all();
    }

    /**
     * A listed product with everything its page shows (only active variants).
     */
    public function findListedProduct(string $slug): ?Product
    {
        return $this->listedProducts(ListingScope::wholeShop())
            ->where('slug', $slug)
            ->with([
                'category.parent',
                'brand',
                'images',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('id'),
                'variants.attributeValues.attribute',
                'variants.image',
            ])
            ->first();
    }

    /**
     * Listed products from the given categories, products of $preferredCategoryId first, without the excluded product.
     *
     * @param  list<int>  $categoryIds
     * @return EloquentCollection<int, Product>
     */
    public function relatedProducts(array $categoryIds, int $preferredCategoryId, int $excludeProductId, int $limit): EloquentCollection
    {
        return $this->listedProducts(ListingScope::categories($categoryIds))
            ->whereKeyNot($excludeProductId)
            ->tap($this->withCardData(...))
            ->orderByRaw('category_id = ? desc', [$preferredCategoryId])
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findListedCategory(string $slug): ?Category
    {
        return Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'parent.children' => fn ($query) => $query->where('is_active', true)->ordered(),
                'children' => fn ($query) => $query->where('is_active', true)->ordered(),
            ])
            ->first();
    }

    /**
     * @return list<int>
     */
    public function categoryWithChildrenIds(Category $category): array
    {
        return Category::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereKey($category->id)->orWhere('parent_id', $category->id))
            ->pluck('id')
            ->all();
    }

    /**
     * Departments with how many listed products they (and their subcategories) have.
     *
     * @return list<array{slug: string, name: string, count: int}>
     */
    public function departmentCounts(ListingScope $scope): array
    {
        return $this->departmentsWithCounts($scope)
            ->map(fn (array $tile): array => [
                'slug' => $tile['department']->slug,
                'name' => $tile['department']->name,
                'count' => $tile['count'],
            ])
            ->all();
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public function brandsInScope(ListingScope $scope): array
    {
        $brandIds = $this->listedProducts($scope)->whereNotNull('brand_id')->distinct()->pluck('brand_id');

        return Brand::query()
            ->whereKey($brandIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['slug', 'name', 'name_bn'])
            ->map(fn (Brand $brand): array => ['slug' => $brand->slug, 'name' => $brand->name])
            ->all();
    }

    /**
     * Values of one attribute (by slug) that active variants of listed products use.
     *
     * @return Collection<int, AttributeValue>
     */
    public function attributeValuesInScope(string $attributeSlug, ListingScope $scope): Collection
    {
        $productIds = $this->listedProducts($scope)->select('id');

        return AttributeValue::query()
            ->whereHas('attribute', fn (Builder $attribute) => $attribute->where('slug', $attributeSlug))
            ->whereHas('variants', fn (Builder $variants) => $variants->where('is_active', true)->whereIn('product_id', $productIds))
            ->ordered()
            ->get(['id', 'value', 'color_hex']);
    }

    /**
     * Highest discount among listed discounted products (Deals header).
     */
    public function highestDiscount(): int
    {
        return (int) $this->listedProducts(ListingScope::deals())->max('max_discount_percent');
    }

    /**
     * Best sellers ("You may like these" on the no-results search page, home page).
     *
     * @return EloquentCollection<int, Product>
     */
    public function bestSellers(int $limit): EloquentCollection
    {
        return $this->cardProducts(ListingScope::wholeShop())
            ->orderByDesc('sold_count')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Featured products for the home page, newest first.
     *
     * @return EloquentCollection<int, Product>
     */
    public function featuredProducts(int $limit): EloquentCollection
    {
        return $this->cardProducts(ListingScope::wholeShop())
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Discounted products, biggest discount first (home page "Today's Deals").
     *
     * @return EloquentCollection<int, Product>
     */
    public function biggestDeals(int $limit): EloquentCollection
    {
        return $this->cardProducts(ListingScope::deals())
            ->orderByDesc('max_discount_percent')
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function newestProducts(int $limit): EloquentCollection
    {
        return $this->cardProducts(ListingScope::wholeShop())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Departments that have listed products, in menu order, with their image and product count.
     *
     * @return list<array{name: string, slug: string, imageUrl: string|null, productCount: int}>
     */
    public function departmentTiles(int $limit): array
    {
        return $this->departmentsWithCounts(ListingScope::wholeShop())
            ->take($limit)
            ->map(fn (array $tile): array => [
                'name' => $tile['department']->name,
                'slug' => $tile['department']->slug,
                'imageUrl' => $tile['department']->imageUrl(),
                'productCount' => $tile['count'],
            ])
            ->all();
    }

    /**
     * Listed products with what a product card needs.
     *
     * @return Builder<Product>
     */
    private function cardProducts(ListingScope $scope): Builder
    {
        return $this->listedProducts($scope)->tap($this->withCardData(...));
    }

    /**
     * Card relations, has_stock, and active_variant_count (a single-variant product is added to the cart from its card, F-021).
     *
     * @param  Builder<Product>  $query
     */
    private function withCardData(Builder $query): void
    {
        $query->with(['category:id,name', 'coverImage', 'cheapestVariant'])
            ->withExists(['variants as has_stock' => fn (Builder $variants) => $variants->where('is_active', true)->where('stock_quantity', '>', 0)])
            ->withCount(['variants as active_variant_count' => fn (Builder $variants) => $variants->where('is_active', true)]);
    }

    /**
     * Active departments with listed products (their own and their subcategories'), in menu order.
     *
     * @return Collection<int, array{department: Category, count: int}>
     */
    private function departmentsWithCounts(ListingScope $scope): Collection
    {
        $countsByCategory = $this->listedProducts($scope)
            ->toBase()
            ->selectRaw('category_id, count(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        return Category::query()
            ->topLevel()
            ->where('is_active', true)
            ->ordered()
            ->with('children:id,parent_id')
            ->get(['id', 'name', 'name_bn', 'slug', 'image'])
            ->map(fn (Category $department): array => [
                'department' => $department,
                'count' => (int) $countsByCategory->only([$department->id, ...$department->children->modelKeys()])->sum(),
            ])
            ->filter(fn (array $tile): bool => $tile['count'] > 0)
            ->values()
            ->toBase();
    }

    /**
     * @return Builder<Product>
     */
    private function listedProducts(ListingScope $scope): Builder
    {
        return Product::query()
            ->where('status', ProductStatus::Active)
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true))
            ->when($scope->categoryIds !== null, fn (Builder $query) => $query->whereIn('category_id', $scope->categoryIds))
            ->when($scope->onlyDiscounted, fn (Builder $query) => $query->where('max_discount_percent', '>', 0))
            ->when($scope->isSearch(), fn (Builder $query) => $this->applySearch($query, $scope->searchWords));
    }

    /**
     * Every word must match the name, a variant SKU, the brand or the category (F-018).
     *
     * @param  Builder<Product>  $query
     * @param  list<string>  $words
     */
    private function applySearch(Builder $query, array $words): void
    {
        foreach ($words as $word) {
            $term = '%'.$this->escapeLike($word).'%';

            $query->where(fn (Builder $matches) => $matches
                ->whereLike('name', $term)
                ->orWhereLike('name_bn', $term)
                ->orWhereHas('variants', fn (Builder $variants) => $variants->whereLike('sku', $term))
                ->orWhereHas('brand', fn (Builder $brand) => $brand->whereLike('name', $term)->orWhereLike('name_bn', $term))
                ->orWhereHas('category', fn (Builder $category) => $category->whereLike('name', $term)->orWhereLike('name_bn', $term)));
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyFilters(Builder $query, ProductListingFilter $filter): void
    {
        $query
            ->when($filter->categorySlugs !== [], fn (Builder $query) => $query->whereIn('category_id', $this->idsOfCategoriesAndChildren($filter->categorySlugs)))
            ->when($filter->brandSlugs !== [], fn (Builder $query) => $query->whereHas('brand', fn (Builder $brand) => $brand->whereIn('slug', $filter->brandSlugs)))
            ->when($filter->priceMin !== null, fn (Builder $query) => $query->where('min_price', '>=', $filter->priceMin))
            ->when($filter->priceMax !== null, fn (Builder $query) => $query->where('min_price', '<=', $filter->priceMax))
            ->when($filter->minRating !== null, fn (Builder $query) => $query->where('rating_average', '>=', $filter->minRating))
            ->when($filter->minDiscount !== null, fn (Builder $query) => $query->where('max_discount_percent', '>=', $filter->minDiscount))
            ->when($filter->onlyInStock, fn (Builder $query) => $query->whereHas('variants', fn (Builder $variants) => $variants->where('is_active', true)->where('stock_quantity', '>', 0)))
            ->when($filter->onlyPreorder, fn (Builder $query) => $query->where('allow_preorder', true))
            ->when($filter->colorValueIds !== [], fn (Builder $query) => $this->whereHasActiveVariantWith($query, $filter->colorValueIds))
            ->when($filter->sizeValueIds !== [], fn (Builder $query) => $this->whereHasActiveVariantWith($query, $filter->sizeValueIds));
    }

    /**
     * @param  Builder<Product>  $query
     * @param  list<int>  $attributeValueIds
     */
    private function whereHasActiveVariantWith(Builder $query, array $attributeValueIds): void
    {
        $query->whereHas('variants', fn (Builder $variants) => $variants
            ->where('is_active', true)
            ->whereHas('attributeValues', fn (Builder $values) => $values->whereKey($attributeValueIds)));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, ProductSort $sort, ListingScope $scope): void
    {
        if ($sort === ProductSort::BestMatch && $scope->searchPhrase !== null) {
            $phrase = $this->escapeLike($scope->searchPhrase);
            $query->orderByRaw(
                'case when name = ? then 0 when name like ? then 1 when name like ? then 2 else 3 end',
                [$scope->searchPhrase, $phrase.'%', '%'.$phrase.'%'],
            );
        }

        match ($sort) {
            ProductSort::BestMatch => $query->orderByDesc('sold_count'),
            ProductSort::Popular => $query->orderByDesc('sold_count')->orderByDesc('published_at'),
            ProductSort::Newest => $query->orderByDesc('published_at'),
            ProductSort::PriceLowToHigh => $query->orderBy('min_price'),
            ProductSort::PriceHighToLow => $query->orderByDesc('min_price'),
            ProductSort::HighestRated => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
        };

        $query->orderByDesc('id');
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function idsOfCategoriesAndChildren(array $slugs): array
    {
        $ids = Category::query()->whereIn('slug', $slugs)->pluck('id');

        return Category::query()->whereKey($ids)->orWhereIn('parent_id', $ids)->pluck('id')->all();
    }
}
