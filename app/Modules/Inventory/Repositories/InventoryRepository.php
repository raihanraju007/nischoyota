<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Data\StockFilter;
use App\Modules\Inventory\Enums\StockListTab;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reads Catalog's variants and is the only writer of product_variants.stock_quantity (ADR-013).
 */
final class InventoryRepository
{
    private const VARIANTS_PER_PAGE = 25;

    private const MOVEMENTS_PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function paginateVariants(StockFilter $filter, int $lowStockThreshold): LengthAwarePaginator
    {
        return $this->variantQuery($filter->search)
            ->tap(fn (Builder $query) => $this->applyTab($query, $filter->tab, $lowStockThreshold))
            ->with(['product', 'attributeValues.attribute'])
            ->orderBy('stock_quantity')
            ->orderBy('id')
            ->paginate(self::VARIANTS_PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, int> tab value => count
     */
    public function countPerTab(?string $search, int $lowStockThreshold): array
    {
        return collect(StockListTab::cases())
            ->mapWithKeys(fn (StockListTab $tab): array => [
                $tab->value => $this->variantQuery($search)->tap(fn (Builder $query) => $this->applyTab($query, $tab, $lowStockThreshold))->count(),
            ])
            ->all();
    }

    /**
     * Active variants at or below the threshold, emptiest first (dashboard, F-028).
     *
     * @return Collection<int, ProductVariant>
     */
    public function lowStockVariants(int $lowStockThreshold, int $limit): Collection
    {
        return $this->lowStockQuery($lowStockThreshold)
            ->with(['product.coverImage', 'attributeValues.attribute'])
            ->orderBy('stock_quantity')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function lowStockCount(int $lowStockThreshold): int
    {
        return $this->lowStockQuery($lowStockThreshold)->count();
    }

    public function loadVariantDetails(ProductVariant $variant): ProductVariant
    {
        return $variant->load(['product', 'attributeValues.attribute']);
    }

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateMovements(ProductVariant $variant): LengthAwarePaginator
    {
        return StockMovement::query()
            ->where('product_variant_id', $variant->id)
            ->with('user')
            ->latest('id')
            ->paginate(self::MOVEMENTS_PER_PAGE);
    }

    /**
     * Quantity each variant gave to an order, by variant id in ascending order (lock order).
     *
     * @return array<int, int>
     */
    public function takenByOrder(int $orderId): array
    {
        return StockMovement::query()
            ->where('order_id', $orderId)
            ->where('reason', StockMovementReason::OrderPlaced)
            ->whereNotNull('product_variant_id')
            ->groupBy('product_variant_id')
            ->orderBy('product_variant_id')
            ->selectRaw('product_variant_id, -SUM(quantity_change) as quantity_taken')
            ->pluck('quantity_taken', 'product_variant_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
    }

    /**
     * Must run inside a transaction: the row stays locked until it commits.
     */
    public function lockVariant(int $variantId): ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->findOrFail($variantId);
    }

    public function setStock(ProductVariant $variant, int $stockQuantity): void
    {
        $variant->forceFill(['stock_quantity' => $stockQuantity])->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordMovement(array $attributes): StockMovement
    {
        return StockMovement::query()->create($attributes);
    }

    /**
     * @return Builder<ProductVariant>
     */
    private function lowStockQuery(int $lowStockThreshold): Builder
    {
        return ProductVariant::query()->where('is_active', true)->where('stock_quantity', '<=', $lowStockThreshold);
    }

    /**
     * @return Builder<ProductVariant>
     */
    private function variantQuery(?string $search): Builder
    {
        return ProductVariant::query()
            ->when($search, function (Builder $query) use ($search): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn (Builder $query) => $query
                    ->whereLike('sku', $term)
                    ->orWhereHas('product', fn (Builder $product) => $product->whereLike('name', $term)));
            });
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    private function applyTab(Builder $query, StockListTab $tab, int $lowStockThreshold): void
    {
        match ($tab) {
            StockListTab::LowStock => $query->whereBetween('stock_quantity', [1, $lowStockThreshold]),
            StockListTab::OutOfStock => $query->where('stock_quantity', '<=', 0),
            StockListTab::All => null,
        };
    }
}
