<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Account\Models\User;
use App\Modules\Order\Data\AdminOrderFilter;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class OrderRepository
{
    private const PER_PAGE = 10;

    private const ADMIN_PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForCustomer(User $user, ?OrderStatus $status): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->withSum('items as item_count', 'quantity')
            ->latest('placed_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentForCustomer(User $user, int $limit): Collection
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->withSum('items as item_count', 'quantity')
            ->latest('placed_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * With items and status history, for the detail and tracking pages.
     */
    public function findForCustomer(User $user, string $orderNumber): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'statusHistory'])
            ->first();
    }

    public function findForTracking(string $orderNumber, string $phone): ?Order
    {
        return Order::query()
            ->where('order_number', $orderNumber)
            ->where('customer_phone', $phone)
            ->with(['items', 'statusHistory'])
            ->first();
    }

    /**
     * Must run inside a transaction: the row stays locked until it commits.
     */
    public function lockForUpdate(Order $order): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($order->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Order $order, array $attributes): void
    {
        $order->update($attributes);
    }

    /**
     * Pending payment rows of the order become $status (paid or cancelled).
     */
    public function settlePendingPayments(Order $order, PaymentStatus $status): void
    {
        $order->payments()->where('status', PaymentStatus::Pending)->update([
            'status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForAdmin(AdminOrderFilter $filter): LengthAwarePaginator
    {
        return $this->adminQuery($filter)
            ->when($filter->status !== null, fn (Builder $query) => $query->where('status', $filter->status))
            ->withSum('items as item_count', 'quantity')
            ->latest('placed_at')
            ->latest('id')
            ->paginate(self::ADMIN_PER_PAGE)
            ->withQueryString();
    }

    /**
     * Orders per status for the tabs, with the search and date range applied.
     *
     * @return array<string, int> status value => count
     */
    public function countPerStatusForAdmin(AdminOrderFilter $filter): array
    {
        return $this->adminQuery($filter)
            ->toBase()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function countWithStatus(OrderStatus $status): int
    {
        return Order::query()->where('status', $status)->count();
    }

    /**
     * Items, history with who changed it, payments and the customer account, for the admin detail page.
     */
    public function loadAdminDetails(Order $order): Order
    {
        return $order->load(['items', 'statusHistory.user', 'payments', 'user']);
    }

    /**
     * @param  list<int>  $orderIds
     * @return Collection<int, Order>
     */
    public function findManyWithItems(array $orderIds): Collection
    {
        return Order::query()->whereIn('id', $orderIds)->with('items')->orderBy('id')->get();
    }

    public function loadItems(Order $order): Order
    {
        return $order->load('items');
    }

    /**
     * Order count, delivered total and cancelled count per customer account.
     *
     * @param  list<int>  $userIds
     * @return array<int, array{order_count: int, delivered_total: int, cancelled_count: int}>
     */
    public function statsForCustomers(array $userIds): array
    {
        return Order::query()
            ->toBase()
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as delivered_total', [OrderStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_count', [OrderStatus::Cancelled->value])
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->user_id => [
                'order_count' => (int) $row->order_count,
                'delivered_total' => (int) $row->delivered_total,
                'cancelled_count' => (int) $row->cancelled_count,
            ]])
            ->all();
    }

    public function hasDeliveredProduct(int $userId, int $productId): bool
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatus::Delivered)
            ->whereHas('items', fn (Builder $items) => $items->where('product_id', $productId))
            ->exists();
    }

    /**
     * Booked sales (not cancelled or returned) placed in [$from, $until).
     *
     * @return array{total: int, count: int}
     */
    public function salesBetween(CarbonInterface $from, CarbonInterface $until): array
    {
        $row = $this->bookedSalesQuery()
            ->where('placed_at', '>=', $from)
            ->where('placed_at', '<', $until)
            ->selectRaw('COALESCE(SUM(total), 0) as sales_total, COUNT(*) as order_count')
            ->first();

        return ['total' => (int) $row->sales_total, 'count' => (int) $row->order_count];
    }

    /**
     * Booked sales per local day from $from on; days are shifted by $utcOffsetSeconds (Bangladesh time).
     *
     * @return array<string, array{total: int, count: int}> 'Y-m-d' => sales
     */
    public function salesPerDay(CarbonInterface $from, int $utcOffsetSeconds): array
    {
        return $this->bookedSalesQuery()
            ->where('placed_at', '>=', $from)
            ->selectRaw('DATE(DATE_ADD(placed_at, INTERVAL ? SECOND)) as sales_day', [$utcOffsetSeconds])
            ->selectRaw('SUM(total) as sales_total, COUNT(*) as order_count')
            ->groupBy('sales_day')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->sales_day => ['total' => (int) $row->sales_total, 'count' => (int) $row->order_count]])
            ->all();
    }

    /**
     * @return array<string, int> status value => count, all time
     */
    public function countPerStatus(): array
    {
        return Order::query()
            ->toBase()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return Collection<int, Order>
     */
    public function latest(int $limit): Collection
    {
        return Order::query()->latest('placed_at')->latest('id')->limit($limit)->get();
    }

    public function countForCustomer(int $userId): int
    {
        return Order::query()->where('user_id', $userId)->count();
    }

    private function bookedSalesQuery(): QueryBuilder
    {
        return Order::query()->toBase()->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value]);
    }

    /**
     * @return Builder<Order>
     */
    private function adminQuery(AdminOrderFilter $filter): Builder
    {
        $startsAt = $filter->dateRange->startsAt();

        return Order::query()
            ->when($startsAt !== null, fn (Builder $query) => $query->where('placed_at', '>=', $startsAt))
            ->when($filter->search !== null, function (Builder $query) use ($filter): void {
                $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filter->search);
                $digits = preg_replace('/\D/', '', $filter->search) ?? '';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('order_number', '%'.ltrim($term, '#').'%')
                    ->orWhereLike('customer_name', '%'.$term.'%')
                    ->when(strlen($digits) >= 4, fn (Builder $query) => $query->orWhereLike('customer_phone', '%'.$digits.'%')));
            });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Order
    {
        $order = Order::query()->create($attributes);
        $order->update(['order_number' => Order::numberFor($order->id)]);

        return $order;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function createItems(Order $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    public function recordStatus(Order $order, ?OrderStatus $fromStatus, OrderStatus $toStatus, ?int $userId, ?string $note = null): void
    {
        $order->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => $userId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createPayment(Order $order, array $attributes): void
    {
        $order->payments()->create($attributes);
    }

    /**
     * Earlier orders with this coupon code by the same customer: placed from their account or with their
     * phone, so switching between guest and signed-in checkout does not reset the per-customer limit.
     */
    public function couponUsageCount(string $couponCode, ?int $userId, string $phone): int
    {
        return Order::query()
            ->where('coupon_code', $couponCode)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->where(fn ($query) => $query
                ->when($phone !== '', fn ($query) => $query->where('customer_phone', $phone))
                ->when($userId !== null, fn ($query) => $query->orWhere('user_id', $userId)))
            ->count();
    }

    public function findByNumber(string $orderNumber): ?Order
    {
        return Order::query()->where('order_number', $orderNumber)->first();
    }
}
