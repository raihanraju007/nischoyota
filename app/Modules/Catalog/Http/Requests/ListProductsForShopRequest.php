<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Data\ProductListingFilter;
use App\Modules\Catalog\Enums\ProductSort;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Storefront filters from the query string. Shared links may be hand-edited, so unknown values are
 * dropped instead of failing validation.
 */
final class ListProductsForShopRequest extends FormRequest
{
    private const RATING_CHOICES = [2, 3, 4];

    private const DISCOUNT_CHOICES = [10, 20, 40];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * The search box text (F-018): trimmed, at most 100 characters; null when empty.
     */
    public function searchQuery(): ?string
    {
        $query = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $this->query('q', '')) ?? ''), 0, 100);

        return $query === '' ? null : $query;
    }

    public function toFilter(ProductSort $defaultSort = ProductSort::Popular): ProductListingFilter
    {
        $availability = $this->stringList('availability');

        return new ProductListingFilter(
            categorySlugs: $this->stringList('category'),
            brandSlugs: $this->stringList('brand'),
            priceMin: $this->takaAsPaisa('price_min'),
            priceMax: $this->takaAsPaisa('price_max'),
            minRating: $this->choice('rating', self::RATING_CHOICES),
            minDiscount: $this->choice('discount', self::DISCOUNT_CHOICES),
            onlyInStock: in_array('in-stock', $availability, true),
            onlyPreorder: in_array('pre-order', $availability, true),
            colorValueIds: $this->idList('color'),
            sizeValueIds: $this->idList('size'),
            sort: ProductSort::tryFrom($this->string('sort')->toString()) ?? $defaultSort,
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(string $key): array
    {
        $values = $this->query($key, []);

        return is_array($values)
            ? array_values(array_unique(array_filter(array_map(fn (mixed $value): string => is_string($value) ? mb_substr(trim($value), 0, 100) : '', $values))))
            : [];
    }

    /**
     * @return list<int>
     */
    private function idList(string $key): array
    {
        return array_values(array_unique(array_filter(array_map(intval(...), $this->stringList($key)), fn (int $id): bool => $id > 0)));
    }

    /**
     * @param  list<int>  $choices
     */
    private function choice(string $key, array $choices): ?int
    {
        $value = (int) $this->query($key);

        return in_array($value, $choices, true) ? $value : null;
    }

    private function takaAsPaisa(string $key): ?int
    {
        $value = $this->query($key);

        return is_numeric($value) && (float) $value >= 0 && (float) $value <= 10_000_000 ? Money::fromTaka($value) : null;
    }
}
