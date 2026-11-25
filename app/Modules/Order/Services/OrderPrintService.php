<?php

namespace App\Modules\Order\Services;

use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Content\Data\StoreSettings;
use App\Modules\Content\Services\SettingService;
use App\Modules\Order\Data\OrderStatusChangeData;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Packing slips and order copies for the browser's print dialog (F-025b).
 */
final class OrderPrintService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusService $orderStatusService,
        private readonly ProductVariantService $productVariantService,
        private readonly SettingService $settingService,
    ) {}

    public function store(): StoreSettings
    {
        return $this->settingService->current();
    }

    public function loadForPrint(Order $order): Order
    {
        return $this->orderRepository->loadItems($order);
    }

    /**
     * @param  list<int>  $orderIds
     * @return Collection<int, Order>
     */
    public function findForPrint(array $orderIds): Collection
    {
        return $this->orderRepository->findManyWithItems($orderIds);
    }

    /**
     * Parcel weight per order from the variants' current weights; null when no item has a weight.
     * Needs items loaded.
     *
     * @param  iterable<Order>  $orders
     * @return array<int, int|null> order id => grams
     */
    public function parcelWeights(iterable $orders): array
    {
        $orders = collect($orders);
        $variantIds = $orders->flatMap(fn (Order $order) => $order->items->pluck('product_variant_id'))->filter()->unique()->values()->all();
        $gramsPerVariant = $this->productVariantService->weightsInGrams($variantIds);

        return $orders->mapWithKeys(function (Order $order) use ($gramsPerVariant): array {
            $weighed = $order->items->filter(fn (OrderItem $item): bool => isset($gramsPerVariant[$item->product_variant_id]));

            return [$order->id => $weighed->isEmpty()
                ? null
                : $weighed->sum(fn (OrderItem $item): int => $gramsPerVariant[$item->product_variant_id] * $item->quantity)];
        })->all();
    }

    /**
     * Confirmed orders whose slip was printed move to Processing; the others stay as they are.
     * Returns how many moved.
     *
     * @param  iterable<Order>  $orders
     */
    public function moveConfirmedToProcessing(iterable $orders, int $userId): int
    {
        $movedCount = 0;

        foreach ($orders as $order) {
            if ($order->status !== OrderStatus::Confirmed) {
                continue;
            }

            $this->orderStatusService->change($order, new OrderStatusChangeData(
                toStatus: OrderStatus::Processing,
                note: __('Packing slip printed'),
                courierName: null,
                trackingCode: null,
                userId: $userId,
            ));
            $movedCount++;
        }

        return $movedCount;
    }
}
