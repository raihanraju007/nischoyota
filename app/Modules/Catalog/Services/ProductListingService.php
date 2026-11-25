<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\FilterOptions;
use App\Modules\Catalog\Data\ListingScope;
use App\Modules\Catalog\Data\ProductCardData;
use App\Modules\Catalog\Data\ProductListingFilter;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductListingRepository;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Storefront listing (F-016): products as cards, filter options and active-filter chips.
 */
final class ProductListingService
{
    private const NEW_BADGE_DAYS = 14;

    private const BEST_SELLER_MIN_SOLD = 50;

    private const MAX_SEARCH_WORDS = 5;

    public function __construct(private readonly ProductListingRepository $listingRepository) {}

    /**
     * @return LengthAwarePaginator<int, ProductCardData>
     */
    public function cards(ListingScope $scope, ProductListingFilter $filter): LengthAwarePaginator
    {
        return $this->listingRepository
            ->paginate($scope, $filter)
            ->through($this->toCard(...));
    }

    public function filterOptions(ListingScope $scope): FilterOptions
    {
        $values = fn (string $slug) => $this->listingRepository->attributeValuesInScope($slug, $scope);

        return new FilterOptions(
            categories: $this->listingRepository->departmentCounts($scope),
            brands: $this->listingRepository->brandsInScope($scope),
            colors: $values(ProductListingRepository::COLOR_ATTRIBUTE_SLUG)
                ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'value' => $value->value, 'hex' => $value->color_hex])->values()->all(),
            sizes: $values(ProductListingRepository::SIZE_ATTRIBUTE_SLUG)
                ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'value' => $value->value])->values()->all(),
        );
    }

    /**
     * Up to $limit cards from the product's department (its whole subtree), its own subcategory first.
     *
     * @return list<ProductCardData>
     */
    public function relatedCards(Product $product, int $limit = 4): array
    {
        $department = $product->category->parent ?? $product->category;
        $departmentCategoryIds = $this->listingRepository->categoryWithChildrenIds($department);

        return $this->listingRepository
            ->relatedProducts($departmentCategoryIds, $product->category_id, $product->id, $limit)
            ->map($this->toCard(...))
            ->all();
    }

    /**
     * Cards for the listed products among $productIds, in the order given (F-022 wishlist).
     *
     * @param  list<int>  $productIds
     * @return list<ProductCardData>
     */
    public function cardsFor(array $productIds): array
    {
        $products = $this->listingRepository->cardProductsByIds($productIds)->keyBy('id');

        return collect($productIds)
            ->filter(fn (int $id): bool => $products->has($id))
            ->map(fn (int $id): ProductCardData => $this->toCard($products->get($id)))
            ->values()
            ->all();
    }

    /**
     * The ids among $productIds that are listed on the storefront (active, in an active category).
     *
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function listedProductIds(array $productIds): array
    {
        return $productIds === [] ? [] : $this->listingRepository->listedProductIds($productIds);
    }

    /**
     * @return list<ProductCardData>
     */
    public function bestSellerCards(int $limit = 4): array
    {
        return $this->listingRepository->bestSellers($limit)->map($this->toCard(...))->all();
    }

    /**
     * Search scope from what the customer typed: up to 5 words, each at least 1 character.
     */
    public function searchScope(string $query): ListingScope
    {
        $words = array_slice(array_values(array_filter(preg_split('/\s+/u', $query) ?: [], fn (string $word): bool => $word !== '')), 0, self::MAX_SEARCH_WORDS);

        return ListingScope::search($query, $words);
    }

    public function findListedCategory(string $slug): ?Category
    {
        return $this->listingRepository->findListedCategory($slug);
    }

    /**
     * @return list<int>
     */
    public function categoryWithChildrenIds(Category $category): array
    {
        return $this->listingRepository->categoryWithChildrenIds($category);
    }

    public function highestDiscount(): int
    {
        return $this->listingRepository->highestDiscount();
    }

    /**
     * Hours until today's deals end at midnight, Bangladesh time (deal countdowns).
     */
    public function dealHoursLeft(): int
    {
        $endOfDealDay = now(config('shop.timezone'))->endOfDay();

        return (int) ceil(now()->diffInMinutes($endOfDealDay) / 60);
    }

    /**
     * Removable chips for the active filters: each links to the current URL without that filter.
     *
     * @param  array<string, mixed>  $query  Current query string
     * @return list<array{label: string, url: string}>
     */
    public function activeFilterChips(ProductListingFilter $filter, FilterOptions $options, array $query, string $baseUrl): array
    {
        $without = function (string $key, mixed $value = null) use ($query, $baseUrl): string {
            $remaining = $query;
            unset($remaining['page']);

            if ($value === null || ! is_array($remaining[$key] ?? null)) {
                unset($remaining[$key]);
            } else {
                $remaining[$key] = array_values(array_diff($remaining[$key], [(string) $value]));
            }

            return $remaining === [] ? $baseUrl : $baseUrl.'?'.http_build_query($remaining);
        };

        $nameOf = fn (array $items, string $key, string|int $value, string $labelKey) => collect($items)->firstWhere($key, $value)[$labelKey] ?? (string) $value;
        $chips = [];

        foreach ($filter->categorySlugs as $slug) {
            $chips[] = ['label' => $nameOf($options->categories, 'slug', $slug, 'name'), 'url' => $without('category', $slug)];
        }
        foreach ($filter->brandSlugs as $slug) {
            $chips[] = ['label' => $nameOf($options->brands, 'slug', $slug, 'name'), 'url' => $without('brand', $slug)];
        }
        if ($filter->priceMin !== null || $filter->priceMax !== null) {
            $range = ($filter->priceMin === null ? '' : Money::format($filter->priceMin)).' – '.($filter->priceMax === null ? '' : Money::format($filter->priceMax));
            $chips[] = ['label' => trim($range), 'url' => $this->withoutKeys($query, $baseUrl, ['price_min', 'price_max'])];
        }
        if ($filter->minRating !== null) {
            $chips[] = ['label' => __(':stars★ & up', ['stars' => $filter->minRating]), 'url' => $without('rating')];
        }
        if ($filter->minDiscount !== null) {
            $chips[] = ['label' => __(':percent% or more off', ['percent' => $filter->minDiscount]), 'url' => $without('discount')];
        }
        if ($filter->onlyInStock) {
            $chips[] = ['label' => __('In Stock'), 'url' => $without('availability', 'in-stock')];
        }
        if ($filter->onlyPreorder) {
            $chips[] = ['label' => __('Pre-order'), 'url' => $without('availability', 'pre-order')];
        }
        foreach ($filter->colorValueIds as $id) {
            $chips[] = ['label' => $nameOf($options->colors, 'id', $id, 'value'), 'url' => $without('color', $id)];
        }
        foreach ($filter->sizeValueIds as $id) {
            $chips[] = ['label' => __('Size :size', ['size' => $nameOf($options->sizes, 'id', $id, 'value')]), 'url' => $without('size', $id)];
        }

        return $chips;
    }

    public function toCard(Product $product): ProductCardData
    {
        $variant = $product->cheapestVariant;
        $price = $variant->price ?? $product->min_price;
        $oldPrice = $variant?->compare_price > $price ? $variant->compare_price : null;
        $isAvailable = (bool) $product->getAttribute('has_stock') || $product->allow_preorder;

        return new ProductCardData(
            name: $product->name,
            url: route('product', $product->slug),
            imageUrl: $product->coverImage?->url(),
            categoryName: $product->category->name,
            price: $price,
            oldPrice: $oldPrice,
            discountPercent: ProductVariant::discountPercentFor($price, $oldPrice),
            rating: (float) $product->rating_average,
            reviewCount: $product->rating_count,
            badge: $this->badgeFor($product),
            isAvailable: $isAvailable,
            quickAddVariantId: $isAvailable && (int) $product->getAttribute('active_variant_count') === 1 ? $variant?->id : null,
            productId: $product->id,
            unitLabel: $product->unitLabel(),
        );
    }

    public function badgeFor(Product $product): ?string
    {
        return match (true) {
            $product->sold_count >= self::BEST_SELLER_MIN_SOLD => 'best',
            $product->published_at?->greaterThan(now()->subDays(self::NEW_BADGE_DAYS)) === true => 'new',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  list<string>  $keys
     */
    private function withoutKeys(array $query, string $baseUrl, array $keys): string
    {
        $remaining = array_diff_key($query, array_flip([...$keys, 'page']));

        return $remaining === [] ? $baseUrl : $baseUrl.'?'.http_build_query($remaining);
    }
}
