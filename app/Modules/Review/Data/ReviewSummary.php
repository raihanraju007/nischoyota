<?php

namespace App\Modules\Review\Data;

/**
 * Approved reviews of one product: average, count and how many gave each star (5 → 1).
 */
final readonly class ReviewSummary
{
    /**
     * @param  array<int, int>  $countPerRating  stars => count, 5 down to 1
     */
    public function __construct(
        public float $average,
        public int $count,
        public array $countPerRating,
    ) {}

    public function percentFor(int $stars): int
    {
        return $this->count === 0 ? 0 : (int) round($this->countPerRating[$stars] / $this->count * 100);
    }
}
