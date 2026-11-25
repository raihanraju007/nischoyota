<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

final class ProductVariantRepository
{
    /**
     * Product with variants (values + attribute, photo) and images, for the Variants page.
     */
    public function loadForVariantsPage(Product $product): Product
    {
        return $product->load([
            'variants' => fn ($query) => $query->orderBy('id'),
            'variants.attributeValues.attribute',
            'images',
        ]);
    }

    /**
     * @return list<int>
     */
    public function activeVariantIds(int $productId): array
    {
        return ProductVariant::query()->where('product_id', $productId)->where('is_active', true)->limit(2)->pluck('id')->all();
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => weight in grams, only variants that have one
     */
    public function weightsInGrams(array $variantIds): array
    {
        return ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->whereNotNull('weight_grams')
            ->pluck('weight_grams', 'id')
            ->map(fn ($grams): int => (int) $grams)
            ->all();
    }

    public function findWithProductAndCategory(int $variantId): ?ProductVariant
    {
        return ProductVariant::query()->with('product.category')->find($variantId);
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function variantsWithValues(Product $product): Collection
    {
        return $product->variants()->with('attributeValues')->orderBy('id')->get();
    }

    /**
     * @param  list<int>  $valueIds
     * @return Collection<int, AttributeValue>
     */
    public function valuesWithAttribute(array $valueIds): Collection
    {
        return AttributeValue::query()->with('attribute')->whereKey($valueIds)->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attributeValueIds
     */
    public function create(Product $product, array $attributes, array $attributeValueIds): ProductVariant
    {
        $variant = $product->variants()->create($attributes);
        $variant->attributeValues()->attach($attributeValueIds);

        return $variant;
    }

    /**
     * @param  list<int>  $attributeValueIds
     */
    public function replaceValues(ProductVariant $variant, array $attributeValueIds): void
    {
        $variant->attributeValues()->sync($attributeValueIds);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProductVariant $variant, array $attributes): void
    {
        $variant->update($attributes);
    }

    /**
     * @param  list<int>  $variantIds
     */
    public function delete(array $variantIds): void
    {
        ProductVariant::query()->whereKey($variantIds)->delete();
    }

    public function makeDefault(Product $product, int $variantId): void
    {
        $product->variants()->whereKeyNot($variantId)->update(['is_default' => false]);
        $product->variants()->whereKey($variantId)->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function activeVariants(Product $product): Collection
    {
        return $product->variants()->where('is_active', true)->get();
    }

    /**
     * @param  array<string, int>  $attributes
     */
    public function updateProduct(Product $product, array $attributes): void
    {
        $product->forceFill($attributes)->save();
    }

    public function isValueInUse(AttributeValue $value): bool
    {
        return $value->variants()->exists();
    }

    public function isAttributeInUse(ProductAttribute $attribute): bool
    {
        return AttributeValue::query()->where('attribute_id', $attribute->id)->has('variants')->exists();
    }
}
