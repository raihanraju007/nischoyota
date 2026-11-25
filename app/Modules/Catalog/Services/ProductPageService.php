<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\ProductCardData;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductListingRepository;
use App\Modules\Content\Services\SettingService;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Services\DeliveryZoneService;
use App\Support\Money;
use App\Support\SafeMarkdown;

/**
 * Data for the storefront product page (F-017).
 */
final class ProductPageService
{
    public function __construct(
        private readonly ProductListingRepository $listingRepository,
        private readonly ProductListingService $listingService,
        private readonly DeliveryZoneService $zoneService,
        private readonly SettingService $settingService,
    ) {}

    public function findListedProduct(string $slug): ?Product
    {
        return $this->listingRepository->findListedProduct($slug);
    }

    public function descriptionHtml(Product $product): string
    {
        return SafeMarkdown::toHtml($product->description);
    }

    /**
     * One group per attribute used by the active variants, attributes by name, values in their sort order.
     *
     * @return list<array{attributeId: int, name: string, values: list<array{id: int, label: string, hex: ?string}>}>
     */
    public function optionGroups(Product $product): array
    {
        return $product->variants
            ->flatMap->attributeValues
            ->unique('id')
            ->groupBy('attribute_id')
            ->map(fn ($values) => [
                'attributeId' => $values->first()->attribute_id,
                'name' => $values->first()->attribute->name,
                'values' => $values
                    ->sortBy(fn (AttributeValue $value): array => [$value->sort_order, $value->value])
                    ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'label' => $value->value, 'hex' => $value->color_hex])
                    ->values()
                    ->all(),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * Everything the variant picker swaps when a combination is chosen. Prices are pre-formatted.
     *
     * @return list<array<string, mixed>>
     */
    public function pickerVariants(Product $product): array
    {
        $lowStockThreshold = $this->settingService->current()->lowStockThreshold;

        return $product->variants->map(function (ProductVariant $variant) use ($product, $lowStockThreshold): array {
            $oldPrice = $variant->compare_price > $variant->price ? $variant->compare_price : null;
            $stock = $this->stockNote($variant, $product->allow_preorder, $lowStockThreshold);

            return [
                'id' => $variant->id,
                'valueIds' => $variant->attributeValues->modelKeys(),
                'label' => $variant->attributeValues->isEmpty() ? null : $variant->label(),
                'sku' => $variant->sku,
                'price' => Money::format($variant->price),
                'oldPrice' => $oldPrice === null ? null : Money::format($oldPrice),
                'discount' => ProductVariant::discountPercentFor($variant->price, $oldPrice),
                'stockText' => $stock['text'],
                'stockClass' => $stock['class'],
                'canBuy' => $stock['canBuy'],
                'imageUrl' => $variant->image?->url(),
                'isDefault' => $variant->is_default,
            ];
        })->values()->all();
    }

    /**
     * @return array{text: string, class: string, canBuy: bool}
     */
    public function stockNote(ProductVariant $variant, bool $allowsPreorder, int $lowStockThreshold): array
    {
        return match (true) {
            $variant->stock_quantity > $lowStockThreshold => ['text' => __('✔ In Stock — ships within 24 hours'), 'class' => 'stock-in', 'canBuy' => true],
            $variant->stock_quantity > 0 => ['text' => __('Only :count left — order soon', ['count' => $variant->stock_quantity]), 'class' => 'stock-low', 'canBuy' => true],
            $allowsPreorder => ['text' => __('Available for pre-order'), 'class' => 'stock-low', 'canBuy' => true],
            default => ['text' => __('Out of stock'), 'class' => 'stock-out', 'canBuy' => false],
        };
    }

    /**
     * @return array{minDays: int, maxDays: int, zones: list<array{name: string, charge: string, days: string}>, freeFrom: ?string}
     */
    public function deliverySummary(): array
    {
        $zones = $this->zoneService->activeZones();
        $threshold = $this->settingService->current()->freeDeliveryThreshold;

        return [
            'minDays' => (int) $zones->min('min_days'),
            'maxDays' => (int) $zones->max('max_days'),
            'zones' => $zones->map(fn (DeliveryZone $zone): array => [
                'name' => $zone->name,
                'charge' => Money::format($zone->charge),
                'days' => $zone->deliveryTimeLabel(),
            ])->all(),
            'freeFrom' => $threshold === null ? null : Money::format($threshold),
        ];
    }

    /**
     * @return list<ProductCardData>
     */
    public function relatedCards(Product $product): array
    {
        return $this->listingService->relatedCards($product);
    }

    /**
     * Same rule as the card badge (F-016).
     */
    public function badge(Product $product): ?string
    {
        return $this->listingService->badgeFor($product);
    }
}
