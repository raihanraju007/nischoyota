<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Content\Services\SettingService;
use App\Modules\Inventory\Data\StockAdjustmentData;
use App\Modules\Inventory\Data\StockFilter;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\InventoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes stock (ADR-013): row lock + stock update + movement in one transaction.
 */
final class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $inventoryRepository,
        private readonly SettingService $settingService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function paginateVariants(StockFilter $filter): LengthAwarePaginator
    {
        return $this->inventoryRepository->paginateVariants($filter, $this->lowStockThreshold());
    }

    /**
     * @return array<string, int>
     */
    public function countPerTab(StockFilter $filter): array
    {
        return $this->inventoryRepository->countPerTab($filter->search, $this->lowStockThreshold());
    }

    /**
     * Stock at or below this (and above 0) counts as low. Store setting (F-010).
     */
    public function lowStockThreshold(): int
    {
        return $this->settingService->current()->lowStockThreshold;
    }

    /**
     * Active variants at or below the low-stock threshold, out of stock first (dashboard, F-028).
     *
     * @return Collection<int, ProductVariant>
     */
    public function lowStockVariants(int $limit): Collection
    {
        return $this->inventoryRepository->lowStockVariants($this->lowStockThreshold(), $limit);
    }

    public function lowStockCount(): int
    {
        return $this->inventoryRepository->lowStockCount($this->lowStockThreshold());
    }

    public function loadVariantDetails(ProductVariant $variant): ProductVariant
    {
        return $this->inventoryRepository->loadVariantDetails($variant);
    }

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateMovements(ProductVariant $variant): LengthAwarePaginator
    {
        return $this->inventoryRepository->paginateMovements($variant);
    }

    /**
     * Returns null when a stock count matches the current stock (nothing to record).
     *
     * @throws InsufficientStockException
     */
    public function adjust(int $variantId, StockAdjustmentData $data): ?StockMovement
    {
        return DB::transaction(function () use ($variantId, $data): ?StockMovement {
            $variant = $this->inventoryRepository->lockVariant($variantId);
            $change = $data->reason->quantityChangeFor($data->quantity, $variant->stock_quantity);

            if ($change === 0) {
                return null;
            }

            return $this->applyChange($variant, $change, $data->reason, $data->note, $data->userId);
        });
    }

    /**
     * Stock a new variant starts with (product form "Opening stock").
     */
    public function recordOpeningStock(int $variantId, int $quantity, ?int $userId): ?StockMovement
    {
        if ($quantity <= 0) {
            return null;
        }

        return $this->adjust($variantId, new StockAdjustmentData(StockMovementReason::OpeningStock, $quantity, note: null, userId: $userId));
    }

    /**
     * Takes an order's quantity from stock (F-023). Must run inside the order's transaction.
     * A pre-order product ($allowsBackorder) takes only what is in stock and never goes below 0.
     * Returns how many came from stock.
     *
     * @throws InsufficientStockException
     */
    public function deductForOrder(int $variantId, int $quantity, int $orderId, bool $allowsBackorder): int
    {
        $variant = $this->inventoryRepository->lockVariant($variantId);
        $takenFromStock = $allowsBackorder ? min($quantity, max(0, $variant->stock_quantity)) : $quantity;

        if ($takenFromStock > 0) {
            $this->applyChange($variant, -$takenFromStock, StockMovementReason::OrderPlaced, note: null, userId: null, orderId: $orderId);
        }

        return $takenFromStock;
    }

    /**
     * Gives back exactly what an order took (its order_placed movements), as order_cancelled movements (F-024).
     * Must run inside the cancelling transaction.
     */
    public function restockForCancelledOrder(int $orderId, ?int $userId): void
    {
        $this->giveBackTakenStock($orderId, $userId, StockMovementReason::OrderCancelled);
    }

    /**
     * Puts a returned order's items back in stock as `returned` movements (F-025).
     * Damaged items are written off afterwards with a manual adjustment. Must run inside a transaction.
     */
    public function restockForReturnedOrder(int $orderId, ?int $userId): void
    {
        $this->giveBackTakenStock($orderId, $userId, StockMovementReason::Returned);
    }

    private function giveBackTakenStock(int $orderId, ?int $userId, StockMovementReason $reason): void
    {
        foreach ($this->inventoryRepository->takenByOrder($orderId) as $variantId => $quantityTaken) {
            $variant = $this->inventoryRepository->lockVariant($variantId);
            $this->applyChange($variant, $quantityTaken, $reason, note: null, userId: $userId, orderId: $orderId);
        }
    }

    /**
     * @throws InsufficientStockException
     */
    private function applyChange(ProductVariant $variant, int $change, StockMovementReason $reason, ?string $note, ?int $userId, ?int $orderId = null): StockMovement
    {
        $stockAfter = $variant->stock_quantity + $change;

        if ($stockAfter < 0) {
            throw new InsufficientStockException($variant->stock_quantity);
        }

        $this->inventoryRepository->setStock($variant, $stockAfter);

        return $this->inventoryRepository->recordMovement([
            'product_variant_id' => $variant->id,
            'quantity_change' => $change,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'user_id' => $userId,
            'order_id' => $orderId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
