<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Collection;

/**
 * Attributes and their values (one aggregate).
 */
final class AttributeRepository
{
    /**
     * @return Collection<int, ProductAttribute>
     */
    public function listWithValues(): Collection
    {
        return ProductAttribute::query()
            ->orderBy('name')
            ->with(['values' => fn ($query) => $query->ordered()])
            ->get();
    }

    public function loadOrderedValues(ProductAttribute $attribute): ProductAttribute
    {
        return $attribute->load(['values' => fn ($query) => $query->ordered()]);
    }

    public function findValueOfAttribute(ProductAttribute $attribute, int $valueId): ?AttributeValue
    {
        return $attribute->values()->whereKey($valueId)->first();
    }

    public function slugExists(string $slug): bool
    {
        return ProductAttribute::query()->where('slug', $slug)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ProductAttribute
    {
        return ProductAttribute::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProductAttribute $attribute, array $attributes): ProductAttribute
    {
        $attribute->update($attributes);

        return $attribute;
    }

    /**
     * Values are removed by the foreign key (cascade).
     */
    public function delete(ProductAttribute $attribute): void
    {
        $attribute->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createValue(ProductAttribute $attribute, array $attributes): AttributeValue
    {
        return $attribute->values()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateValue(AttributeValue $value, array $attributes): AttributeValue
    {
        $value->update($attributes);

        return $value;
    }

    public function deleteValue(AttributeValue $value): void
    {
        $value->delete();
    }
}
