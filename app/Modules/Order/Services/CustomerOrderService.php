<?php

namespace App\Modules\Order\Services;

use App\Modules\Account\Models\User;
use App\Modules\Order\Data\BuyAgainResult;
use App\Modules\Order\Data\CustomerOrderStats;
use App\Modules\Order\Data\TimelineStep;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Exceptions\CartException;
use App\Modules\Shopping\Services\CartService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * A customer's placed orders (F-024): list, detail, timeline, cancel, buy again, guest tracking.
 */
final class CustomerOrderService
{
    private const TIME_FORMAT = 'd M Y, h:i A';

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusService $orderStatusService,
        private readonly CartService $cartService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateFor(User $user, ?OrderStatus $status): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForCustomer($user, $status);
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentFor(User $user, int $limit): Collection
    {
        return $this->orderRepository->recentForCustomer($user, $limit);
    }

    /**
     * Order numbers per customer account for the admin customer pages (F-026); customers without orders get zeros.
     *
     * @param  list<int>  $userIds
     * @return array<int, CustomerOrderStats>
     */
    public function statsFor(array $userIds): array
    {
        $rows = $userIds === [] ? [] : $this->orderRepository->statsForCustomers($userIds);

        return collect($userIds)->mapWithKeys(fn (int $userId): array => [$userId => isset($rows[$userId])
            ? new CustomerOrderStats($rows[$userId]['order_count'], $rows[$userId]['delivered_total'], $rows[$userId]['cancelled_count'])
            : new CustomerOrderStats,
        ])->all();
    }

    /**
     * "Verified purchase" for reviews (F-027): a delivered order of this customer contains the product.
     */
    public function hasDeliveredProduct(int $userId, int $productId): bool
    {
        return $this->orderRepository->hasDeliveredProduct($userId, $productId);
    }

    public function findFor(User $user, string $orderNumber): ?Order
    {
        return $this->orderRepository->findForCustomer($user, $orderNumber);
    }

    /**
     * Number and phone must both match. "#ord-10025" works like "ORD-10025".
     */
    public function track(string $orderNumber, string $phone): ?Order
    {
        return $this->orderRepository->findForTracking(mb_strtoupper(ltrim(trim($orderNumber), '#')), $phone);
    }

    /**
     * Cancels a pending / confirmed order: stock back as it was taken, coupon use released, history row.
     *
     * @throws OrderException when the order is past the point of cancelling
     */
    public function cancel(User $user, Order $order): void
    {
        $this->orderStatusService->cancelByCustomer($order, $user->id);
    }

    /**
     * Puts the order's items back in the cart; items that cannot be bought now are skipped.
     */
    public function buyAgain(Order $order, CartOwner $cartOwner): BuyAgainResult
    {
        $addedCount = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            if ($this->addToCart($item, $cartOwner)) {
                $addedCount++;
            } else {
                $skipped[] = $item->product_name;
            }
        }

        return new BuyAgainResult($addedCount, $skipped);
    }

    /**
     * Placed → Confirmed → Processing → Shipped → Delivered, done steps with their time from the history;
     * a cancelled or returned order ends with that step. Needs statusHistory loaded.
     *
     * @return list<TimelineStep>
     */
    public function timeline(Order $order): array
    {
        $reachedAt = $order->statusHistory
            ->sortBy('id')
            ->mapWithKeys(fn (OrderStatusHistory $row): array => [$row->to_status->value => $row->created_at]);

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
            return $this->endedTimeline($order, $reachedAt->all());
        }

        $currentIndex = array_search($order->status, OrderStatus::progressSteps(), true);

        return array_map(function (OrderStatus $step) use ($order, $reachedAt, $currentIndex): TimelineStep {
            $index = array_search($step, OrderStatus::progressSteps(), true);
            $state = match (true) {
                $index < $currentIndex, $step === OrderStatus::Delivered && $index === $currentIndex => 'done',
                $index === $currentIndex => 'current',
                default => 'upcoming',
            };

            return new TimelineStep($step->stepTitle(), $this->noteFor($order, $step, $state, $reachedAt->get($step->value)), $state);
        }, OrderStatus::progressSteps());
    }

    private function addToCart(OrderItem $item, CartOwner $cartOwner): bool
    {
        if ($item->product_variant_id === null) {
            return false;
        }

        try {
            $this->cartService->add($cartOwner, $item->product_variant_id, $item->quantity);
        } catch (CartException) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, CarbonInterface>  $reachedAt
     * @return list<TimelineStep>
     */
    private function endedTimeline(Order $order, array $reachedAt): array
    {
        $steps = [];

        foreach ([...OrderStatus::progressSteps(), $order->status] as $step) {
            if (isset($reachedAt[$step->value])) {
                $steps[] = new TimelineStep($step->stepTitle(), $this->formatTime($reachedAt[$step->value]), 'done');
            }
        }

        return $steps;
    }

    private function noteFor(Order $order, OrderStatus $step, string $state, ?CarbonInterface $reachedAt): string
    {
        return match (true) {
            $reachedAt !== null => $this->formatTime($reachedAt),
            $state === 'upcoming' && $step === OrderStatus::Delivered => __('Estimated :dates', ['dates' => $order->estimatedDeliveryLabel()]),
            $state === 'upcoming' => __('Pending'),
            default => '',
        };
    }

    private function formatTime(CarbonInterface $moment): string
    {
        return $moment->timezone(config('shop.timezone'))->format(self::TIME_FORMAT);
    }
}
