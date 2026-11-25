<?php

namespace App\Modules\Dashboard\Data;

/**
 * Cached dashboard numbers (F-028). Money in paisa. Stored in the cache as an array (toArray / fromArray).
 */
final readonly class DashboardStats
{
    /**
     * @param  array<string, int>  $ordersPerStatus  status value => count
     * @param  list<array{label: string, date: string, sales: int, orders: int}>  $salesChart
     */
    public function __construct(
        public int $salesToday,
        public int $ordersToday,
        public int $salesThisMonth,
        public int $ordersThisMonth,
        public int $newCustomersThisMonth,
        public array $ordersPerStatus,
        public array $salesChart,
    ) {}

    public function chartTotal(): int
    {
        return array_sum(array_column($this->salesChart, 'sales'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $stats  From toArray()
     */
    public static function fromArray(array $stats): self
    {
        return new self(...$stats);
    }
}
