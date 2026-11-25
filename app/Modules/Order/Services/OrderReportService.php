<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only sales numbers for the admin dashboard (F-028). Sales are booked orders:
 * everything placed except cancelled and returned orders. Days are Bangladesh days.
 */
final class OrderReportService
{
    public function __construct(private readonly OrderRepository $orderRepository) {}

    /**
     * @return array{total: int, count: int} paisa and number of orders
     */
    public function salesSince(CarbonImmutable $localStart): array
    {
        return $this->orderRepository->salesBetween($localStart->utc(), CarbonImmutable::now()->addSecond());
    }

    /**
     * One entry per local day for the last $days days (today included), days without sales as zero.
     *
     * @return list<array{date: CarbonImmutable, total: int, count: int}>
     */
    public function dailySales(int $days): array
    {
        $today = CarbonImmutable::now(config('shop.timezone'))->startOfDay();
        $firstDay = $today->subDays($days - 1);
        $salesPerDay = $this->orderRepository->salesPerDay($firstDay->utc(), $today->getOffset());

        return array_map(function (int $offset) use ($firstDay, $salesPerDay): array {
            $day = $firstDay->addDays($offset);
            $sales = $salesPerDay[$day->format('Y-m-d')] ?? ['total' => 0, 'count' => 0];

            return ['date' => $day, ...$sales];
        }, range(0, $days - 1));
    }

    /**
     * @return array<string, int> every status value => count, all time
     */
    public function countPerStatus(): array
    {
        $perStatus = $this->orderRepository->countPerStatus();

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $perStatus[$status->value] ?? 0])
            ->all();
    }

    /**
     * @return Collection<int, Order>
     */
    public function latest(int $limit): Collection
    {
        return $this->orderRepository->latest($limit);
    }
}
