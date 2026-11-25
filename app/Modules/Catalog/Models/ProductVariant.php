<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Money columns are paisa. cost_price is admin-only (ADR-012) and hidden from serialization.
 */
#[Fillable(['product_id', 'product_image_id', 'sku', 'price', 'compare_price', 'cost_price', 'stock_quantity', 'weight_grams', 'is_default', 'is_active'])]
#[Hidden(['cost_price'])]
#[UseFactory(ProductVariantFactory::class)]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_price' => 'integer',
            'cost_price' => 'integer',
            'stock_quantity' => 'integer',
            'weight_grams' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The option values that make up this variant (Color = White, Size = 56"). Empty for a simple product.
     *
     * @return BelongsToMany<AttributeValue, $this>
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_product_variant');
    }

    /**
     * The variant's own photo (one of the product's images), shown when the customer picks it.
     *
     * @return BelongsTo<ProductImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    /**
     * "White / 56\"" ordered by attribute name; needs attributeValues.attribute loaded. "Default" for a simple product.
     */
    public function label(): string
    {
        if ($this->attributeValues->isEmpty()) {
            return __('Default');
        }

        return $this->attributeValues
            ->sortBy(fn (AttributeValue $value): string => $value->attribute->name)
            ->pluck('value')
            ->implode(' / ');
    }

    /**
     * Sorted attribute value ids, e.g. "3-7": identifies the combination.
     */
    public function combinationKey(): string
    {
        return self::combinationKeyFor($this->attributeValues->modelKeys());
    }

    /**
     * @param  array<int, int|string>  $attributeValueIds
     */
    public static function combinationKeyFor(array $attributeValueIds): string
    {
        return collect($attributeValueIds)->map(intval(...))->sort()->implode('-');
    }

    /**
     * floor((old − price) / old × 100); 0 without a higher old price.
     */
    public static function discountPercentFor(int $price, ?int $comparePrice): int
    {
        if ($comparePrice === null || $comparePrice <= $price) {
            return 0;
        }

        return intdiv(($comparePrice - $price) * 100, $comparePrice);
    }

    /**
     * Can be put in a cart: the variant is active and its product is listed. Needs product.category loaded.
     */
    public function isSellable(): bool
    {
        return $this->is_active && $this->product->isListed();
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }

    /**
     * @param  int  $lowStockThreshold  Store setting (SettingService, F-010)
     */
    public function isLowOnStock(int $lowStockThreshold): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $lowStockThreshold;
    }
}
