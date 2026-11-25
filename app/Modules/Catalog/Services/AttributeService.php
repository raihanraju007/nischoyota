<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\AttributeData;
use App\Modules\Catalog\Data\AttributeValueData;
use App\Modules\Catalog\Exceptions\AttributeInUseException;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Repositories\AttributeRepository;
use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Collection;

final class AttributeService
{
    private const FALLBACK_SLUG = 'attribute';

    public function __construct(
        private readonly AttributeRepository $attributeRepository,
        private readonly ProductVariantRepository $variantRepository,
    ) {}

    /**
     * @return Collection<int, ProductAttribute>
     */
    public function listWithValues(): Collection
    {
        return $this->attributeRepository->listWithValues();
    }

    public function loadOrderedValues(ProductAttribute $attribute): ProductAttribute
    {
        return $this->attributeRepository->loadOrderedValues($attribute);
    }

    /**
     * The value to edit from "?value={id}"; null when absent or when it belongs to another attribute.
     */
    public function findValueOfAttribute(ProductAttribute $attribute, ?int $valueId): ?AttributeValue
    {
        return $valueId === null ? null : $this->attributeRepository->findValueOfAttribute($attribute, $valueId);
    }

    public function create(AttributeData $data): ProductAttribute
    {
        return $this->attributeRepository->create([
            'name' => $data->name,
            'slug' => $data->slug ?? $this->uniqueSlugFor($data->name),
        ]);
    }

    public function update(ProductAttribute $attribute, AttributeData $data): ProductAttribute
    {
        return $this->attributeRepository->update($attribute, [
            'name' => $data->name,
            'slug' => $data->slug ?? ($attribute->name === $data->name ? $attribute->slug : $this->uniqueSlugFor($data->name)),
        ]);
    }

    /**
     * @throws AttributeInUseException
     */
    public function delete(ProductAttribute $attribute): void
    {
        if ($this->variantRepository->isAttributeInUse($attribute)) {
            throw AttributeInUseException::attribute();
        }

        $this->attributeRepository->delete($attribute);
    }

    public function addValue(ProductAttribute $attribute, AttributeValueData $data): AttributeValue
    {
        return $this->attributeRepository->createValue($attribute, $this->valueAttributesFrom($data));
    }

    public function updateValue(AttributeValue $value, AttributeValueData $data): AttributeValue
    {
        return $this->attributeRepository->updateValue($value, $this->valueAttributesFrom($data));
    }

    /**
     * @throws AttributeInUseException
     */
    public function deleteValue(AttributeValue $value): void
    {
        if ($this->variantRepository->isValueInUse($value)) {
            throw AttributeInUseException::value();
        }

        $this->attributeRepository->deleteValue($value);
    }

    private function uniqueSlugFor(string $name): string
    {
        return UniqueSlug::generate($name, $this->attributeRepository->slugExists(...), self::FALLBACK_SLUG);
    }

    /**
     * @return array<string, mixed>
     */
    private function valueAttributesFrom(AttributeValueData $data): array
    {
        return [
            'value' => $data->value,
            'color_hex' => $data->colorHex,
            'sort_order' => $data->sortOrder,
        ];
    }
}
