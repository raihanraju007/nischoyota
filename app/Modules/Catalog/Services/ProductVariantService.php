<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\VariantRowData;
use App\Modules\Catalog\Exceptions\InvalidVariantSetupException;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductVariantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Variants are combinations of attribute values (ADR-007, D5). A simple product has one default variant without values.
 */
final class ProductVariantService
{
    public const MAX_ATTRIBUTES = 3;

    public const MAX_COMBINATIONS = 100;

    public function __construct(
        private readonly ProductVariantRepository $variantRepository,
        private readonly ProductSkuGenerator $skuGenerator,
    ) {}

    public function loadForVariantsPage(Product $product): Product
    {
        return $this->variantRepository->loadForVariantsPage($product);
    }

    /**
     * The id of the product's only active variant; null when it has options (several variants) or none.
     */
    public function singleActiveVariantId(int $productId): ?int
    {
        $variantIds = $this->variantRepository->activeVariantIds($productId);

        return count($variantIds) === 1 ? $variantIds[0] : null;
    }

    /**
     * Current shipping weights for packing slips (F-025b). Variants without a weight are left out.
     *
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => grams
     */
    public function weightsInGrams(array $variantIds): array
    {
        return $variantIds === [] ? [] : $this->variantRepository->weightsInGrams($variantIds);
    }

    /**
     * A variant with its product and category, whatever its status: the cart (F-021) decides with isSellable().
     */
    public function findForCart(int $variantId): ?ProductVariant
    {
        return $this->variantRepository->findWithProductAndCategory($variantId);
    }

    /**
     * Turn the chosen values into variants: keep existing combinations, create missing ones, delete the rest.
     * An empty selection turns the product back into a simple product.
     *
     * @param  array<int, list<int>>  $selection  attribute id => chosen value ids
     *
     * @throws InvalidVariantSetupException
     */
    public function generate(Product $product, array $selection): int
    {
        $combinations = $this->combinationsFor(array_filter($selection));

        DB::transaction(function () use ($product, $combinations): void {
            $variants = $this->variantRepository->variantsWithValues($product);
            $template = $variants->firstWhere('is_default', true) ?? $variants->first();

            $combinations === []
                ? $this->makeSimple($product, $variants, $template)
                : $this->syncCombinations($product, $variants, $template, $combinations);

            $this->ensureOneDefault($product);
            $this->refreshCachedPrices($product);
        });

        return max(count($combinations), 1);
    }

    /**
     * @param  list<VariantRowData>  $rows
     *
     * @throws InvalidVariantSetupException
     */
    public function updateVariants(Product $product, array $rows, int $defaultVariantId): void
    {
        $variants = $this->variantRepository->variantsWithValues($product)->keyBy('id');
        $rowsOfProduct = array_filter($rows, fn (VariantRowData $row): bool => $variants->has($row->variantId));
        $defaultRow = collect($rowsOfProduct)->firstWhere('variantId', $defaultVariantId);

        if ($defaultRow === null) {
            throw InvalidVariantSetupException::unknownDefault();
        }

        if (! $defaultRow->isActive) {
            throw InvalidVariantSetupException::defaultMustBeActive();
        }

        DB::transaction(function () use ($product, $variants, $rowsOfProduct, $defaultVariantId): void {
            foreach ($rowsOfProduct as $row) {
                $this->variantRepository->update($variants[$row->variantId], $this->attributesFrom($row, $variants[$row->variantId]));
            }

            $this->variantRepository->makeDefault($product, $defaultVariantId);
            $this->refreshCachedPrices($product);
        });
    }

    /**
     * min_price = lowest active price; max_discount_percent = highest active discount.
     */
    public function refreshCachedPrices(Product $product): void
    {
        $activeVariants = $this->variantRepository->activeVariants($product);

        $this->variantRepository->updateProduct($product, [
            'min_price' => (int) $activeVariants->min('price'),
            'max_discount_percent' => (int) $activeVariants
                ->map(fn (ProductVariant $variant): int => ProductVariant::discountPercentFor($variant->price, $variant->compare_price))
                ->max(),
        ]);
    }

    /**
     * Cartesian product of the chosen values, attributes ordered by name: [[white, 48], [white, 56], …].
     *
     * @param  array<int, list<int>>  $selection
     * @return list<list<int>>
     *
     * @throws InvalidVariantSetupException
     */
    private function combinationsFor(array $selection): array
    {
        if (count($selection) > self::MAX_ATTRIBUTES) {
            throw InvalidVariantSetupException::tooManyAttributes(self::MAX_ATTRIBUTES);
        }

        $groups = $this->valueGroupsByAttribute($selection);
        $count = array_product(array_map(count(...), $groups));

        if ($groups !== [] && $count > self::MAX_COMBINATIONS) {
            throw InvalidVariantSetupException::tooManyCombinations($count, self::MAX_COMBINATIONS);
        }

        if ($groups === []) {
            return [];
        }

        $combinations = [[]];

        foreach ($groups as $valueIds) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($valueIds as $valueId) {
                    $next[] = [...$combination, $valueId];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * Checks every value belongs to the attribute it was posted under.
     *
     * @param  array<int, list<int>>  $selection
     * @return list<list<int>>
     */
    private function valueGroupsByAttribute(array $selection): array
    {
        $values = $this->variantRepository->valuesWithAttribute(array_merge(...array_values($selection) ?: [[]]))->toBase()->keyBy('id');
        $groups = [];

        foreach ($selection as $attributeId => $valueIds) {
            foreach ($valueIds as $valueId) {
                if ($values->get($valueId)?->attribute_id !== $attributeId) {
                    throw InvalidVariantSetupException::valueNotInAttribute();
                }
            }

            $attributeName = $values[$valueIds[0]]->attribute->name;
            $groups[$attributeName] = $values->only($valueIds)
                ->sortBy(fn (AttributeValue $value): array => [$value->sort_order, $value->value])
                ->pluck('id')
                ->all();
        }

        ksort($groups);

        return array_values($groups);
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @param  list<list<int>>  $combinations
     */
    private function syncCombinations(Product $product, Collection $variants, ?ProductVariant $template, array $combinations): void
    {
        // toBase(): Eloquent's except()/only() match model ids, but here the keys are combination keys.
        $variantsByKey = $variants->toBase()->keyBy(fn (ProductVariant $variant): string => $variant->combinationKey());
        $simpleVariant = $variantsByKey->pull('');
        $wantedKeys = [];

        foreach ($combinations as $valueIds) {
            $key = ProductVariant::combinationKeyFor($valueIds);
            $wantedKeys[] = $key;

            if ($variantsByKey->has($key)) {
                continue;
            }

            if ($simpleVariant !== null) {
                $this->variantRepository->replaceValues($simpleVariant, $valueIds);
                $variantsByKey->put($key, $simpleVariant);
                $simpleVariant = null;

                continue;
            }

            $variantsByKey->put($key, $this->variantRepository->create($product, $this->newVariantAttributes($product, $template), $valueIds));
        }

        $removedIds = $variantsByKey->except($wantedKeys)->map(fn (ProductVariant $variant): int => $variant->id)->values()->all();

        if ($simpleVariant !== null) {
            $removedIds[] = $simpleVariant->id;
        }

        $this->variantRepository->delete($removedIds);
    }

    /**
     * Keep the default variant without values; delete the others.
     *
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function makeSimple(Product $product, Collection $variants, ?ProductVariant $keep): void
    {
        if ($keep === null) {
            return;
        }

        $this->variantRepository->replaceValues($keep, []);
        $this->variantRepository->delete($variants->except([$keep->id])->modelKeys());
        $this->variantRepository->makeDefault($product, $keep->id);
    }

    private function ensureOneDefault(Product $product): void
    {
        $variants = $this->variantRepository->variantsWithValues($product);
        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        if ($default !== null) {
            $this->variantRepository->makeDefault($product, $default->id);
        }
    }

    /**
     * New combinations copy the template's prices and start with stock 0.
     *
     * @return array<string, mixed>
     */
    private function newVariantAttributes(Product $product, ?ProductVariant $template): array
    {
        return [
            'sku' => $this->skuGenerator->generateFor($product->category_id),
            'price' => $template->price ?? 0,
            'compare_price' => $template?->compare_price,
            'cost_price' => $template?->cost_price,
            'weight_grams' => $template?->weight_grams,
            'stock_quantity' => 0,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(VariantRowData $row, ProductVariant $variant): array
    {
        $attributes = [
            'sku' => $row->sku ?? $variant->sku,
            'price' => $row->price,
            'compare_price' => $row->comparePrice,
            'is_active' => $row->isActive,
            'product_image_id' => $row->productImageId,
        ];

        if ($row->shouldUpdateCostPrice) {
            $attributes['cost_price'] = $row->costPrice;
        }

        return $attributes;
    }
}
