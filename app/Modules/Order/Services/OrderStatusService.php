<?php

namespace App\Modules\Order\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Data\OrderStatusChangeData;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Promotion\Services\CouponService;
use Illuminate\Support\Facades\DB;

/**
 * Moves placed orders between statuses and settles their stock, coupon and payment (F-024, F-025).
 * Every change locks the order row and writes a status history row.
 */
final class OrderStatusService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly InventoryService $inventoryService,
        private readonly CouponService $couponService,
    ) {}

    /**
     * A staff change along OrderStatus::allowedNextStatuses().
     *
     * @throws OrderException
     */
    public function change(Order $order, OrderStatusChangeData $data): void
    {
        DB::transaction(function () use ($order, $data): void {
            $locked = $this->orderRepository->lockForUpdate($order);

            if (! $locked->status->canTransitionTo($data->toStatus)) {
                throw OrderException::invalidTransition($locked->status, $data->toStatus);
            }

            $courierName = $data->courierName ?? $locked->courier_name;

            if ($data->toStatus === OrderStatus::Shipped && $courierName === null) {
                throw OrderException::courierRequired();
            }

            $this->orderRepository->update($locked, [
                'courier_name' => $courierName,
                'tracking_code' => $data->trackingCode ?? $locked->tracking_code,
            ]);
            $this->moveTo($locked, $data->toStatus, $data->userId, $data->note);
        });
    }

    /**
     * Customers cancel their own order while it is pending or confirmed (F-024).
     *
     * @throws OrderException
     */
    public function cancelByCustomer(Order $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId): void {
            $locked = $this->orderRepository->lockForUpdate($order);

            if ($locked->status === OrderStatus::Cancelled) {
                throw OrderException::alreadyCancelled();
            }

            if (! $locked->status->isCancellableByCustomer()) {
                throw OrderException::notCancellable();
            }

            $this->moveTo($locked, OrderStatus::Cancelled, $userId, __('Cancelled by customer'));
        });
    }

    /**
     * The courier paid the cash for a COD order.
     *
     * @throws OrderException
     */
    public function markPaid(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = $this->orderRepository->lockForUpdate($order);

            if ($locked->payment_status !== PaymentStatus::Pending || $locked->status->isFinal()) {
                throw OrderException::notPayable();
            }

            $this->orderRepository->settlePendingPayments($locked, PaymentStatus::Paid);
            $this->orderRepository->update($locked, ['payment_status' => PaymentStatus::Paid]);
        });
    }

    /**
     * Must run inside a transaction, on a locked order whose transition was checked.
     */
    private function moveTo(Order $locked, OrderStatus $toStatus, ?int $userId, ?string $note): void
    {
        $previousStatus = $locked->status;

        $this->orderRepository->update($locked, [
            'status' => $toStatus,
            ...match ($toStatus) {
                OrderStatus::Delivered => ['delivered_at' => now()],
                OrderStatus::Cancelled => ['cancelled_at' => now()],
                default => [],
            },
        ]);
        $this->orderRepository->recordStatus($locked, $previousStatus, $toStatus, $userId, $note);

        match ($toStatus) {
            OrderStatus::Cancelled => $this->applyCancellation($locked, $userId),
            OrderStatus::Returned => $this->applyReturn($locked, $userId),
            default => null,
        };
    }

    /**
     * Stock back as it was taken, coupon use released, payment settled, then OrderCancelled after commit.
     */
    private function applyCancellation(Order $order, ?int $userId): void
    {
        $this->inventoryService->restockForCancelledOrder($order->id, $userId);

        if ($order->coupon_id !== null) {
            $this->couponService->releaseUse($order->coupon_id);
        }

        $this->settlePayment($order);

        OrderCancelled::dispatch($order->id);
    }

    /**
     * Items back in stock; the coupon use stays (the order did happen).
     */
    private function applyReturn(Order $order, ?int $userId): void
    {
        $this->inventoryService->restockForReturnedOrder($order->id, $userId);
        $this->settlePayment($order);
    }

    /**
     * A paid order gets a refund row; an unpaid one has its pending payment cancelled.
     */
    private function settlePayment(Order $order): void
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            $this->orderRepository->createPayment($order, [
                'method' => $order->payment_method,
                'status' => PaymentStatus::Refunded,
                'amount' => $order->total,
            ]);
            $this->orderRepository->update($order, ['payment_status' => PaymentStatus::Refunded]);

            return;
        }

        $this->orderRepository->settlePendingPayments($order, PaymentStatus::Cancelled);
        $this->orderRepository->update($order, ['payment_status' => PaymentStatus::Cancelled]);
    }
}
