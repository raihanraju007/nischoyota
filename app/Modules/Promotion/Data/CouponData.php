<?php

namespace App\Modules\Promotion\Data;

use App\Modules\Promotion\Enums\CouponType;
use Carbon\CarbonImmutable;

/**
 * $value: paisa for fixed, whole percent for percent. Dates already converted to UTC.
 */
final readonly class CouponData
{
    public function __construct(
        public string $code,
        public CouponType $type,
        public int $value,
        public ?int $minOrderAmount,
        public ?int $maxDiscountAmount,
        public ?int $usageLimit,
        public ?int $usageLimitPerUser,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $expiresAt,
        public bool $isActive,
    ) {}
}
