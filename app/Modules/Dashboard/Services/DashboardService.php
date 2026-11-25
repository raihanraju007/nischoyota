<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Account\Services\CustomerService;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Dashboard\Data\DashboardStats;
use App\Modules\Dashboard\Enums\ChartPeriod;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderReportService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The admin dashboard (F-028). Read-only: it only calls other modules' services.
 * Sales numbers are cached for a few minutes; recent orders and low stock are live.
 */
final class DashboardService
{
    private const CACHE_MINUTES = 5;

    private const RECENT_ORDER_COUNT = 6;

    private const LOW_STOCK_COUNT = 6;

    public function __construct(
        private readonly OrderReportService $orderReportService,
        private readonly CustomerService $customerService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function stats(ChartPeriod $period): DashboardStats
    {
        $today = CarbonImmutable::now(config('shop.timezone'))->startOfDay();
        $cacheKey = 'dashboard.stats.'.$period->value.'.'.$today->format('Y-m-d');

        $stats = Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), fn (): array => $this->buildStats($period, $today)->toArray());

        return DashboardStats::fromArray($stats);
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(): Collection
    {
        return $this->orderReportService->latest(self::RECENT_ORDER_COUNT);
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function lowStockVariants(): Collection
    {
        return $this->inventoryService->lowStockVariants(self::LOW_STOCK_COUNT);
    }

    public function lowStockCount(): int
    {
        return $this->inventoryService->lowStockCount();
    }

    private function buildStats(ChartPeriod $period, CarbonImmutable $today): DashboardStats
    {
        $salesToday = $this->orderReportService->salesSince($today);
        $salesThisMonth = $this->orderReportService->salesSince($today->startOfMonth());

        return new DashboardStats(
            salesToday: $salesToday['total'],
            ordersToday: $salesToday['count'],
            salesThisMonth: $salesThisMonth['total'],
            ordersThisMonth: $salesThisMonth['count'],
            newCustomersThisMonth: $this->customerService->countRegisteredSince($today->startOfMonth()->utc()),
            ordersPerStatus: $this->orderReportService->countPerStatus(),
            salesChart: array_map(fn (array $day): array => [
                'label' => $day['date']->format($period->barLabelFormat()),
                'date' => $day['date']->format('d M'),
                'sales' => $day['total'],
                'orders' => $day['count'],
            ], $this->orderReportService->dailySales($period->value)),
        );
    }
}
