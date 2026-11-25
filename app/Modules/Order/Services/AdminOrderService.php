<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Data\AdminOrderFilter;
use App\Modules\Order\Data\TimelineStep;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Order\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read side of admin order management (F-025): list, tab counts, detail, history, internal note.
 * Status and payment changes live in OrderStatusService.
 */
final class AdminOrderService
{
    private const TIME_FORMAT = 'd M Y, h:i A';

    public function __construct(private readonly OrderRepository $orderRepository) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(AdminOrderFilter $filter): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForAdmin($filter);
    }

    /**
     * @return array<string, int> 'all' and each status value => count
     */
    public function countPerTab(AdminOrderFilter $filter): array
    {
        $perStatus = $this->orderRepository->countPerStatusForAdmin($filter);
        $counts = ['all' => array_sum($perStatus)];

        foreach (OrderStatus::adminFilters() as $status) {
            $counts[$status->value] = $perStatus[$status->value] ?? 0;
        }

        return $counts;
    }

    /**
     * New orders waiting for a call (sidebar badge).
     */
    public function pendingCount(): int
    {
        return $this->orderRepository->countWithStatus(OrderStatus::Pending);
    }

    public function loadDetails(Order $order): Order
    {
        return $this->orderRepository->loadAdminDetails($order);
    }

    /**
     * How many orders the customer's account has placed; null for guest orders.
     */
    public function customerOrderCount(Order $order): ?int
    {
        return $order->user_id === null ? null : $this->orderRepository->countForCustomer($order->user_id);
    }

    /**
     * Every history row, oldest first: "24 Sep 2026, 11:05 AM · by Admin User · phone call".
     * The latest row is the current step unless the order has ended. Needs statusHistory.user loaded.
     *
     * @return list<TimelineStep>
     */
    public function history(Order $order): array
    {
        $rows = $order->statusHistory->sortBy('id')->values();
        $lastIndex = $rows->count() - 1;

        return $rows->map(fn (OrderStatusHistory $row, int $index): TimelineStep => new TimelineStep(
            title: $row->to_status->stepTitle(),
            note: $this->historyNote($order, $row),
            state: $index === $lastIndex && ! $order->status->isFinal() && $order->status !== OrderStatus::Delivered ? 'current' : 'done',
        ))->all();
    }

    public function updateNote(Order $order, ?string $note): void
    {
        $this->orderRepository->update($order, ['admin_note' => $note]);
    }

    private function historyNote(Order $order, OrderStatusHistory $row): string
    {
        $changedBy = $row->user_id === null || $row->user_id === $order->user_id
            ? __('by customer')
            : __('by :name', ['name' => $row->user?->name ?? __('deleted user')]);

        return implode(' · ', array_filter([
            $row->created_at->timezone(config('shop.timezone'))->format(self::TIME_FORMAT),
            $changedBy,
            $row->note,
        ]));
    }
}
